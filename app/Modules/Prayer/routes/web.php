<?php

use App\Modules\Prayer\Http\Controllers\PrayerController;
use Illuminate\Support\Facades\Route;

// Routes of the Doa Mingguan module. Loaded with the "web" middleware group.
// Keep every route name under the "prayer." prefix.

Route::middleware(['auth', 'verified'])
    ->prefix('admin/prayer')
    ->name('prayer.')
    ->group(function () {
        Route::get('/', [PrayerController::class, 'index'])->name('index');
    });
