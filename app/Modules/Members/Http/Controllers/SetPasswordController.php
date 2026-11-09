<?php

namespace App\Modules\Members\Http\Controllers;

use App\Concerns\PasswordValidationRules;
use App\Http\Controllers\Controller;
use App\Modules\Members\Enums\MemberStatus;
use App\Modules\Members\Models\Member;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;
use Inertia\Response;

/**
 * "Buat password" page linked from the approval email (PRD 4.4). The link is
 * signed, expires, and stops working once a password is set because it
 * carries a fingerprint of the current password hash.
 */
class SetPasswordController extends Controller
{
    use PasswordValidationRules;

    public static function link(object $member): string
    {
        return URL::temporarySignedRoute(
            'members.password.create',
            now()->addDays((int) config('modules.members.password_link_days')),
            ['member' => $member->getKey(), 'hash' => self::fingerprint($member->getAuthPassword())],
        );
    }

    public function create(Request $request, Member $member): Response
    {
        $this->ensureUsable($request, $member);

        return Inertia::render('members/auth/set-password', [
            'name' => $member->name,
            'email' => $member->email,
            'action' => $request->fullUrl(),
        ]);
    }

    public function store(Request $request, Member $member): RedirectResponse
    {
        $this->ensureUsable($request, $member);

        $validated = $request->validate(['password' => $this->passwordRules()]);

        $member->forceFill([
            'password' => $validated['password'],
            'email_verified_at' => $member->email_verified_at ?? now(),
        ])->save();

        Auth::login($member);
        $request->session()->regenerate();

        return to_route('dashboard');
    }

    private function ensureUsable(Request $request, Member $member): void
    {
        abort_unless(
            $member->status === MemberStatus::Active
                && hash_equals(self::fingerprint($member->getAuthPassword()), (string) $request->query('hash')),
            403,
            'Tautan ini sudah dipakai atau tidak berlaku lagi. Gunakan "Lupa password" di halaman masuk.',
        );
    }

    private static function fingerprint(string $passwordHash): string
    {
        return hash_hmac('sha256', $passwordHash, (string) config('app.key'));
    }
}
