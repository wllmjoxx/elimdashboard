<?php

// Settings for the Kesaksian module, read with config('modules.testimonies.*').

return [

    // Feature flag: when false, the module's routes and sidebar items are not
    // registered (its migrations still run).
    'enabled' => (bool) env('MODULE_TESTIMONIES_ENABLED', true),

    // Submissions per visitor per hour. The limiter keys on a hash of the IP
    // that expires with the window; no IP is ever stored (PRD 4.7).
    'hourly_limit' => 3,

    // Moderation target (PRD 4 goals): older pending testimonies are flagged.
    'moderation_hours' => 48,

    // Testimonies on the homepage carousel when none are featured.
    'home_fallback' => 3,

    'per_page' => 9,

];
