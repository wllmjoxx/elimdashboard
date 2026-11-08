<?php

// Settings for the Renungan module, read with config('modules.devotionals.*').

return [

    // Feature flag: when false, the module's routes and sidebar items are not
    // registered (its migrations still run).
    'enabled' => (bool) env('MODULE_DEVOTIONALS_ENABLED', true),

];
