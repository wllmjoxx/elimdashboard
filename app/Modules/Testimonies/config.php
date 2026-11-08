<?php

// Settings for the Kesaksian module, read with config('modules.testimonies.*').

return [

    // Feature flag: when false, the module's routes and sidebar items are not
    // registered (its migrations still run).
    'enabled' => (bool) env('MODULE_TESTIMONIES_ENABLED', true),

];
