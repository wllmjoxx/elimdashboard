<?php

// Settings for the Landing Page module, read with config('modules.landing.*').

return [

    // Feature flag: when false, the module's routes and sidebar items are not
    // registered (its migrations still run).
    'enabled' => (bool) env('MODULE_LANDING_ENABLED', true),

];
