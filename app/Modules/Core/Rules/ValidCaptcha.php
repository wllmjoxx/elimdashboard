<?php

namespace App\Modules\Core\Rules;

use App\Modules\Core\Support\Captcha;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Passes when the captcha is off or the Turnstile token is valid.
 */
class ValidCaptcha implements ValidationRule
{
    /**
     * Run even when the token is missing, so an empty token fails.
     */
    public bool $implicit = true;

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! app(Captcha::class)->verify(is_string($value) ? $value : null, request()->ip())) {
            $fail('Verifikasi keamanan gagal. Muat ulang halaman dan coba lagi.');
        }
    }
}
