<?php

// Settings for the Cabang & Akses module, read with config('modules.core.*').

return [

    // Feature flag: when false, the module's routes and sidebar items are not
    // registered (its migrations still run).
    'enabled' => (bool) env('MODULE_CORE_ENABLED', true),

];
