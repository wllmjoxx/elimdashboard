<?php

use App\Modules\Devotionals\Http\Controllers\DevotionalsController;
use Illuminate\Support\Facades\Route;

// Routes of the Renungan module. Loaded with the "web" middleware group.
// Keep every route name under the "devotionals." prefix.

Route::middleware(['auth', 'verified'])
    ->prefix('admin/devotionals')
    ->name('devotionals.')
    ->group(function () {
        Route::get('/', [DevotionalsController::class, 'index'])->name('index');
    });
