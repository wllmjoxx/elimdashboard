<?php

use App\Modules\Gallery\Http\Controllers\GalleryController;
use App\Modules\Gallery\Http\Controllers\PublicGalleryController;
use Illuminate\Support\Facades\Route;

// Routes of the Galeri module. Loaded with the "web" middleware group.
// Keep every route name under the "gallery." prefix.

Route::name('gallery.public.')->controller(PublicGalleryController::class)->group(function () {
    Route::get('/galeri', 'index')->name('index');
    Route::get('/galeri/{slug}', 'show')->where('slug', '[a-z0-9-]+')->name('show');
});

Route::middleware(['auth', 'verified'])
    ->prefix('admin/gallery')
    ->name('gallery.')
    ->controller(GalleryController::class)
    ->scopeBindings()
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::get('/{album}', 'show')->name('show');
        Route::put('/{album}', 'update')->name('update');
        Route::delete('/{album}', 'destroy')->name('destroy');
        Route::post('/{album}/photos', 'upload')->name('photos.store');
        Route::put('/{album}/photos/{photo}', 'updatePhoto')->name('photos.update');
        Route::post('/{album}/photos/{photo}/move', 'movePhoto')->name('photos.move');
        Route::delete('/{album}/photos/{photo}', 'destroyPhoto')->name('photos.destroy');
    });
