<?php

use App\Providers\AppServiceProvider;
use App\Providers\FortifyServiceProvider;
use App\Providers\ModulesServiceProvider;

// Do not add feature modules here: they are discovered automatically from
// app/Modules/*/*ServiceProvider.php by ModulesServiceProvider.
return [
    AppServiceProvider::class,
    FortifyServiceProvider::class,
    ModulesServiceProvider::class,
];
