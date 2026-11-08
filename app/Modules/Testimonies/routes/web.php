<?php

use App\Modules\Testimonies\Http\Controllers\TestimoniesController;
use Illuminate\Support\Facades\Route;

// Routes of the Kesaksian module. Loaded with the "web" middleware group.
// Keep every route name under the "testimonies." prefix.

Route::middleware(['auth', 'verified'])
    ->prefix('admin/testimonies')
    ->name('testimonies.')
    ->group(function () {
        Route::get('/', [TestimoniesController::class, 'index'])->name('index');
    });
