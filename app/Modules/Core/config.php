<?php

// Settings for the Cabang & Akses module, read with config('modules.core.*').

return [

    // Feature flag: when false, the module's routes and sidebar items are not
    // registered (its migrations still run). Keep it on in production: it also
    // switches on branch scoping (PRD 4.10) and the Superadmin bypass.
    'enabled' => (bool) env('MODULE_CORE_ENABLED', true),

    // Slug of the branch that visitors and users without a branch belong to.
    // The first release only has GGP Elim; per-domain branches come in Tahap 5.
    'default_church' => env('CORE_DEFAULT_CHURCH', 'ggp-elim'),

    // Email sign-in codes (PRD 4.4): lifetime, wrong guesses allowed per
    // code, and how often codes may be requested.
    'otp' => [
        'minutes' => 10,
        'max_attempts' => 5,
        'per_email_per_10_minutes' => 3,
        'per_ip_per_hour' => 10,
        'verify_per_minute' => 10,
        // How the code email leaves without making the visitor wait: 'auto'
        // sends it right after the response under PHP-FPM and through the
        // queue elsewhere (php artisan serve); 'after_response' or 'queue'
        // force one way.
        'delivery' => env('OTP_DELIVERY', 'auto'),
    ],

    // Cloudflare Turnstile keys for public forms. Leave empty to turn the
    // captcha off (local development); honeypot and rate limits still apply.
    'captcha' => [
        'site_key' => env('TURNSTILE_SITE_KEY'),
        'secret_key' => env('TURNSTILE_SECRET_KEY'),
    ],

    // Permissions granted to each role (PRD 4.2), as patterns over the names
    // that modules declare in permissions(). "*" matches any part of a name and
    // a leading "!" removes matches. Superadmin is not listed: it passes every
    // check through Gate::before. Apply changes with `php artisan core:sync-permissions`.
    'role_permissions' => [
        'admin' => ['*', '!core.churches.manage', '!core.audit.view'],
        'prayer-team' => ['prayer.view', 'prayer.mark', 'prayer.export'],
        'servant' => ['devotionals.view', 'devotionals.write', 'gallery.view', 'gallery.upload'],
        'member' => [],
    ],

];
