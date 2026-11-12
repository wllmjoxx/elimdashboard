<?php

use App\Modules\Events\Http\Controllers\EventsController;
use App\Modules\Events\Http\Controllers\PublicEventController;
use Illuminate\Support\Facades\Route;

// Routes of the Kegiatan module. Loaded with the "web" middleware group.
// Keep every route name under the "events." prefix.

Route::name('events.public.')->controller(PublicEventController::class)->group(function () {
    Route::get('/kegiatan', 'index')->name('index');
    Route::get('/kegiatan/{slug}', 'show')->where('slug', '[a-z0-9-]+')->name('show');
});

Route::middleware(['auth', 'verified'])
    ->prefix('admin/events')
    ->name('events.')
    ->controller(EventsController::class)
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('/{event}/edit', 'edit')->name('edit');
        // POST with _method=PUT so the image can be sent as multipart.
        Route::put('/{event}', 'update')->name('update');
        Route::delete('/{event}', 'destroy')->name('destroy');
    });
