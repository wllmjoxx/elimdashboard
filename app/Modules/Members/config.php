<?php

// Settings for the Jemaat module, read with config('modules.members.*').

return [

    // Feature flag: when false, the module's routes and sidebar items are not
    // registered (its migrations still run).
    'enabled' => (bool) env('MODULE_MEMBERS_ENABLED', true),

    // How long the "Buat password" link in the approval email stays valid.
    'password_link_days' => (int) env('MEMBERS_PASSWORD_LINK_DAYS', 7),

];
