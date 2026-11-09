<?php

// Settings for the Galeri module, read with config('modules.gallery.*').

return [

    // Feature flag: when false, the module's routes and sidebar items are not
    // registered (its migrations still run).
    'enabled' => (bool) env('MODULE_GALLERY_ENABLED', true),

    'disk' => 'public',
    'directory' => 'gallery',

    // Upload rules shown to the uploader and checked on the server.
    'upload' => [
        'mimes' => ['jpg', 'jpeg', 'png', 'webp'],
        'max_kb' => 10240,
        'min_side' => 600,
        'max_files' => 20,
    ],

    // Automatic compression (PRD 4.8): the longest side is reduced to
    // max_side and saved as WebP (JPEG when the server lacks WebP support).
    // Without the GD extension the original file is kept.
    'compress' => [
        'max_side' => 1920,
        'thumb_side' => 600,
        'quality' => 80,
    ],

    // Photos shown in the homepage gallery section.
    'home_limit' => 8,

];
