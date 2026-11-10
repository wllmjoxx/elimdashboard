<?php

namespace App\Modules\Core\Support;

use App\Models\User;
use App\Modules\Core\Models\LoginOtp;
use App\Modules\Core\Notifications\LoginCode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

use function Illuminate\Support\defer;

/**
 * Email sign-in codes (PRD 4.4): six digits, single use, short-lived, with
 * a limited number of attempts. Only a keyed hash of the code is stored.
 */
class LoginCodes
{
    /**
     * Send a fresh code when the email belongs to an active account. Callers
     * show the same message either way, so nobody can tell which emails
     * are registered.
     */
    public function send(string $email): void
    {
        $email = Str::lower(trim($email));
        $user = User::query()->where('email', $email)->first();

        if (! $user || ! $this->isActive($user)) {
            return;
        }

        $code = str_pad((string) random_int(0, 999_999), 6, '0', STR_PAD_LEFT);
        $minutes = (int) config('modules.core.otp.minutes');

        DB::transaction(function () use ($email, $code, $minutes) {
            // A new code replaces any earlier one for this email.
            LoginOtp::query()->where('email', $email)->delete();

            LoginOtp::query()->create([
                'email' => $email,
                'code_hash' => $this->hash($code),
                'expires_at' => now()->addMinutes($minutes),
            ]);
        });

        // Sent right after the response instead of through the queue: the
        // code is only useful for a few minutes, so it must not wait for a
        // worker, and the visitor does not wait for the mail server either.
        defer(fn () => $user->notifyNow(new LoginCode($code, $minutes)));
    }

    /**
     * The account the code signs in, or null. Every wrong guess counts
     * against the code; after the limit it stops working.
     */
    public function verify(string $email, string $code): ?User
    {
        $email = Str::lower(trim($email));
        $otp = LoginOtp::query()->where('email', $email)->latest('id')->first();

        if (! $otp || ! $otp->isUsable()) {
            return null;
        }

        if (! hash_equals($otp->code_hash, $this->hash(preg_replace('/\D/', '', $code) ?? ''))) {
            $otp->increment('attempts');

            return null;
        }

        $user = User::query()->where('email', $email)->first();

        if (! $user || ! $this->isActive($user)) {
            return null;
        }

        $otp->forceFill(['used_at' => now()])->save();

        return $user;
    }

    private function hash(string $code): string
    {
        return hash_hmac('sha256', $code, (string) config('app.key'));
    }

    /**
     * Accounts without a status column count as active; the Jemaat module
     * adds pending, rejected and inactive.
     */
    private function isActive(User $user): bool
    {
        return in_array($user->getAttribute('status') ?? 'active', ['active'], true);
    }
}
