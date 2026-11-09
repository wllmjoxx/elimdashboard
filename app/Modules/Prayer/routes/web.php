<?php

use App\Modules\Prayer\Http\Controllers\PrayerController;
use App\Modules\Prayer\Http\Controllers\PrayerFormController;
use Illuminate\Support\Facades\Route;

// Routes of the Doa Mingguan module. Loaded with the "web" middleware group.
// Keep every route name under the "prayer." prefix, except "landing.prayer",
// the navbar's Form Doa page, which keeps the name the landing menu uses.

Route::get('/doa', [PrayerFormController::class, 'create'])->name('landing.prayer');
Route::post('/doa', [PrayerFormController::class, 'store'])
    ->middleware('throttle:prayer-form')
    ->name('prayer.store');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/doa/riwayat', [PrayerFormController::class, 'history'])->name('prayer.history');
});

Route::middleware(['auth', 'verified'])
    ->prefix('admin/prayer')
    ->name('prayer.')
    ->controller(PrayerController::class)
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/print', 'print')->name('print');
        Route::post('/{prayerRequest}/status', 'updateStatus')->name('status');
        Route::delete('/{prayerRequest}', 'destroy')->name('destroy');
    });
