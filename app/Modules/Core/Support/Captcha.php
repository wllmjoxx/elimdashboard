<?php

namespace App\Modules\Core\Support;

use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Cloudflare Turnstile for public forms (prayer, testimonies; PRD 4.6, 4.7).
 * Off until both keys are set, so local development works without them;
 * honeypot and rate limits still apply.
 */
class Captcha
{
    private const VERIFY_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    public function enabled(): bool
    {
        return filled(config('modules.core.captcha.site_key')) && filled(config('modules.core.captcha.secret_key'));
    }

    public function siteKey(): ?string
    {
        return $this->enabled() ? (string) config('modules.core.captcha.site_key') : null;
    }

    public function verify(?string $token, ?string $ip = null): bool
    {
        if (! $this->enabled()) {
            return true;
        }

        if (blank($token)) {
            return false;
        }

        try {
            return (bool) Http::asForm()->timeout(5)->post(self::VERIFY_URL, array_filter([
                'secret' => config('modules.core.captcha.secret_key'),
                'response' => $token,
                'remoteip' => $ip,
            ]))->json('success', false);
        } catch (Throwable) {
            return false;
        }
    }
}
