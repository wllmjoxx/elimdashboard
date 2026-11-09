<?php

use App\Modules\Devotionals\Http\Controllers\DevotionalsController;
use App\Modules\Devotionals\Http\Controllers\PublicDevotionalController;
use Illuminate\Support\Facades\Route;

// Routes of the Renungan module. Loaded with the "web" middleware group.
// Keep every route name under the "devotionals." prefix.

// Public "Renungan Harian" pages (navbar item of the landing site).
Route::name('devotionals.public.')->controller(PublicDevotionalController::class)->group(function () {
    Route::get('/renungan', 'index')->name('index');
    Route::get('/renungan/{slug}', 'show')->where('slug', '[a-z0-9-]+')->name('show');
});

Route::middleware(['auth', 'verified'])
    ->prefix('admin/devotionals')
    ->name('devotionals.')
    ->controller(DevotionalsController::class)
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('/{devotional}/edit', 'edit')->name('edit');
        Route::put('/{devotional}', 'update')->name('update');
        Route::post('/{devotional}/publish', 'publish')->name('publish');
        Route::post('/{devotional}/unpublish', 'unpublish')->name('unpublish');
        Route::delete('/{devotional}', 'destroy')->name('destroy');
    });
