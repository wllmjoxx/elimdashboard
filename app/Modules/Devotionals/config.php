<?php

// Settings for the Renungan module, read with config('modules.devotionals.*').

return [

    // Feature flag: when false, the module's routes and sidebar items are not
    // registered (its migrations still run).
    'enabled' => (bool) env('MODULE_DEVOTIONALS_ENABLED', true),

    // "Today" for publish dates. All branches are in WIB for now.
    'timezone' => 'Asia/Jakarta',

    // Devotionals per page in the public archive.
    'archive_per_page' => 12,

];
