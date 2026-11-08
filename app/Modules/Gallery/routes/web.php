<?php

use App\Modules\Gallery\Http\Controllers\GalleryController;
use Illuminate\Support\Facades\Route;

// Routes of the Galeri module. Loaded with the "web" middleware group.
// Keep every route name under the "gallery." prefix.

Route::middleware(['auth', 'verified'])
    ->prefix('admin/gallery')
    ->name('gallery.')
    ->group(function () {
        Route::get('/', [GalleryController::class, 'index'])->name('index');
    });
