<?php

use App\Modules\Landing\Http\Controllers\HomeController;
use App\Modules\Landing\Http\Controllers\LandingController;
use Illuminate\Support\Facades\Route;

// Routes of the Landing Page module. Loaded with the "web" middleware group.
// Keep every route name under the "landing." prefix, except the public
// homepage, which keeps the framework-wide name "home".

Route::get('/', HomeController::class)->name('home');

Route::middleware(['auth', 'verified'])
    ->prefix('admin/landing')
    ->name('landing.')
    ->group(function () {
        Route::get('/', [LandingController::class, 'index'])->name('index');
    });
