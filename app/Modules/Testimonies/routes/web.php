<?php

use App\Modules\Testimonies\Http\Controllers\PublicTestimonyController;
use App\Modules\Testimonies\Http\Controllers\TestimoniesController;
use Illuminate\Support\Facades\Route;

// Routes of the Kesaksian module. Loaded with the "web" middleware group.
// Keep every route name under the "testimonies." prefix.

Route::name('testimonies.public.')->controller(PublicTestimonyController::class)->group(function () {
    Route::get('/kesaksian', 'index')->name('index');
    Route::post('/kesaksian', 'store')->middleware('throttle:testimony-form')->name('store');
});

Route::middleware(['auth', 'verified'])
    ->prefix('admin/testimonies')
    ->name('testimonies.')
    ->controller(TestimoniesController::class)
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::put('/{testimony}', 'update')->name('update');
        Route::post('/{testimony}/moderate', 'moderate')->name('moderate');
        Route::post('/{testimony}/feature', 'feature')->name('feature');
        Route::delete('/{testimony}', 'destroy')->name('destroy');
    });
