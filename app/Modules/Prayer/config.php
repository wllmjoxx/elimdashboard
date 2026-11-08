<?php

// Settings for the Doa Mingguan module, read with config('modules.prayer.*').

return [

    // Feature flag: when false, the module's routes and sidebar items are not
    // registered (its migrations still run).
    'enabled' => (bool) env('MODULE_PRAYER_ENABLED', true),

];
