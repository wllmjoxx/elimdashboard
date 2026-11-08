<?php

// Settings for the Galeri module, read with config('modules.gallery.*').

return [

    // Feature flag: when false, the module's routes and sidebar items are not
    // registered (its migrations still run).
    'enabled' => (bool) env('MODULE_GALLERY_ENABLED', true),

];
