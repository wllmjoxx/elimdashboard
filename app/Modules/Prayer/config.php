<?php

// Settings for the Doa Mingguan module, read with config('modules.prayer.*').

return [

    // Feature flag: when false, the module's routes and sidebar items are not
    // registered (its migrations still run).
    'enabled' => (bool) env('MODULE_PRAYER_ENABLED', true),

    // Weeks run Monday to Sunday in this timezone (PRD 4.6).
    'timezone' => 'Asia/Jakarta',

    // Requests a signed-in member may send per week (PRD 4.6).
    'member_weekly_limit' => 3,

    // Guest submissions per IP address per hour, on top of the captcha.
    'guest_hourly_limit' => 3,

];
