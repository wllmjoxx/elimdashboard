<?php

// Settings for the Jemaat module, read with config('modules.members.*').

return [

    // Feature flag: when false, the module's routes and sidebar items are not
    // registered (its migrations still run).
    'enabled' => (bool) env('MODULE_MEMBERS_ENABLED', true),

];
