<?php

namespace App\Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Support\LoginCodes;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * "Masuk dengan kode email" (PRD 4.4): the visitor asks for a code, then
 * types it in. Accounts with two-factor authentication still pass the
 * two-factor challenge afterwards.
 */
class OtpLoginController extends Controller
{
    public function __construct(private readonly LoginCodes $codes) {}

    public function create(): Response
    {
        return Inertia::render('core/auth/otp-request', [
            'minutes' => (int) config('modules.core.otp.minutes'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $email = $request->validate(['email' => ['required', 'string', 'email', 'max:255']])['email'];

        $this->codes->send($email);

        // Same answer whether or not the email exists.
        return to_route('core.otp.verify', ['email' => $email])
            ->with('status', 'Jika email tersebut terdaftar dan aktif, kode masuk sudah dikirim. Periksa juga folder spam.');
    }

    public function edit(Request $request): Response|RedirectResponse
    {
        $email = (string) $request->query('email');

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return to_route('core.otp.create');
        }

        return Inertia::render('core/auth/otp-verify', [
            'email' => $email,
            'minutes' => (int) config('modules.core.otp.minutes'),
            'status' => $request->session()->get('status'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
            'code' => ['required', 'string', 'regex:/^\s*\d{3}\s?\d{3}\s*$/'],
        ], [
            'code.regex' => 'Kode terdiri dari 6 angka.',
        ], ['code' => 'kode']);

        $user = $this->codes->verify($validated['email'], $validated['code']);

        if (! $user) {
            throw ValidationException::withMessages([
                'code' => 'Kode salah, sudah dipakai, atau sudah kedaluwarsa. Minta kode baru bila perlu.',
            ]);
        }

        // Signing in with a code proves the email address belongs to them.
        if ($user->email_verified_at === null) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        if ($user->two_factor_secret && $user->two_factor_confirmed_at) {
            $request->session()->put(['login.id' => $user->getKey(), 'login.remember' => false]);

            return to_route('two-factor.login');
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }
}
