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

    // Short videos for the first homepage segment (Admin › Landing Page ›
    // Video & Live). They play muted and loop, so keep them short (30–90 s)
    // and compressed. The web server must accept uploads this large too
    // (PHP upload_max_filesize / post_max_size, Nginx client_max_body_size).
    'hero_video' => [
        'disk' => 'public',
        'directory' => 'landing/videos',
        'mimetypes' => ['video/mp4', 'video/webm'],
        'extensions' => ['mp4', 'webm'],
        'max_kb' => 51200,
        'max_count' => 5,
        'poster_mimes' => ['jpg', 'jpeg', 'png', 'webp'],
        'poster_max_kb' => 2048,
        'recommended' => 'MP4 (H.264), 1920 × 1080 atau 1280 × 720, 30–90 detik, tanpa suara',
    ],

    // Live streaming shown in the same frame while Admin marks it live. It is
    // switched off on its own after this many hours, in case nobody does.
    'live' => [
        'auto_off_hours' => 6,
    ],

    // Search engines and link previews (WhatsApp, Facebook). Tags are rendered
    // on the server, so scrapers that do not run JavaScript still see them.
    'seo' => [
        'description' => env('SEO_DESCRIPTION', 'GGP Elim Ciputat, Rumah Kasih Karunia di Tangerang Selatan: jadwal ibadah, renungan harian, form doa, dan kontak gereja.'),
        'legal_name' => 'Gereja Gerakan Pentakosta Jemaat Elim Ciputat',

        // Geo meta tags (ISO 3166-2 region; ID-BT = Banten). Coordinates are
        // optional; set them once the exact pin is confirmed.
        'region' => 'ID-BT',
        'placename' => 'Ciputat, Tangerang Selatan',
        'latitude' => env('SEO_LATITUDE'),
        'longitude' => env('SEO_LONGITUDE'),

        // Structured address for the JSON-LD "Church" block.
        'address' => [
            'street' => 'Gg. Buntu (Citanduy), Cipayung',
            'locality' => 'Ciputat',
            'region' => 'Banten',
            'postal_code' => '15418',
            'country' => 'ID',
        ],

        // Private areas crawlers should skip. Outside production robots.txt
        // disallows everything, so staging never shows up in search results.
        'disallow' => [
            '/admin',
            '/dashboard',
            '/settings',
            '/profil',
            '/login',
            '/register',
            '/set-password',
            '/forgot-password',
            '/reset-password',
            '/two-factor-challenge',
            '/email',
            '/member-photos',
            '/doa/riwayat',
        ],
    ],

];
