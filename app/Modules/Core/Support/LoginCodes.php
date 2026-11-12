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
     * Send a fresh code when the email belongs to an active account.
     *
     * @return 'sent'|'unknown'|'pending'|'inactive' what happened, so the
     *                                               page can tell the visitor
     */
    public function send(string $email): string
    {
        $email = Str::lower(trim($email));
        $user = User::query()->where('email', $email)->first();

        if (! $user) {
            return 'unknown';
        }

        if (! $this->isActive($user)) {
            return $user->getAttribute('status') === 'pending' ? 'pending' : 'inactive';
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

        $this->deliver($user, new LoginCode($code, $minutes));

        return 'sent';
    }

    /**
     * Send the code without making the visitor wait for the mail server.
     *
     * Under PHP-FPM or LiteSpeed (production) the response goes out first and the email
     * right after it, without a queue worker. The PHP development server
     * (`php artisan serve`) cannot finish a response early, so there the
     * email goes to the queue and the worker sends it within seconds.
     */
    private function deliver(User $user, LoginCode $notification): void
    {
        $mode = (string) config('modules.core.otp.delivery', 'auto');

        if ($mode === 'auto') {
            $mode = function_exists('fastcgi_finish_request') || function_exists('litespeed_finish_request')
                ? 'after_response'
                : 'queue';
        }

        if ($mode === 'queue') {
            dispatch(fn () => $user->notifyNow($notification));

            return;
        }

        defer(fn () => $user->notifyNow($notification));
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
