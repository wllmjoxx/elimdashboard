<?php

// Settings for the Landing Page module, read with config('modules.landing.*').

return [

    // Feature flag: when false, the module's routes and sidebar items are not
    // registered (its migrations still run).
    'enabled' => (bool) env('MODULE_LANDING_ENABLED', true),

    // Upload rules for the background image of the three home segments. The
    // admin page shows these to the editor before they upload.
    'segment_image' => [
        'disk' => 'public',
        'directory' => 'landing/segments',
        'mimes' => ['jpg', 'jpeg', 'png', 'webp'],
        'max_kb' => 3072,
        'min_width' => 1600,
        'min_height' => 900,
        'max_width' => 4000,
        'max_height' => 3000,
        // Width divided by height; 1.5 = 3:2. Segments are wide banners, so
        // portrait and square photos are refused.
        'min_ratio' => 1.5,
        'recommended' => '1920 × 1080 px (16:9)',
    ],

];
