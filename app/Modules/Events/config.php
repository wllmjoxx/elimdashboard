<?php

// Settings for the Kegiatan module, read with config('modules.events.*').

return [

    // Feature flag: when false, the module's routes and sidebar items are not
    // registered (its migrations still run).
    'enabled' => (bool) env('MODULE_EVENTS_ENABLED', true),

    // Event times are entered and shown in church time.
    'timezone' => 'Asia/Jakarta',

    // Optional poster or photo per event.
    'image' => [
        'disk' => 'public',
        'directory' => 'events',
        'mimes' => ['jpg', 'jpeg', 'png', 'webp'],
        'max_kb' => 4096,
    ],

    // Events shown in the homepage segment (ongoing first, then upcoming).
    'home_limit' => 4,

    // Past events listed under "Sudah lewat" on /kegiatan.
    'past_limit' => 6,

];
