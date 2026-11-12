<?php

// Settings for the Pengumuman module, read with config('modules.announcements.*').

return [

    // Feature flag: when false, the module's routes and sidebar items are not
    // registered (its migrations still run).
    'enabled' => (bool) env('MODULE_ANNOUNCEMENTS_ENABLED', true),

    // Publish and expiry dates follow church time.
    'timezone' => 'Asia/Jakarta',

    // Announcements per page on /pengumuman.
    'per_page' => 12,

];
