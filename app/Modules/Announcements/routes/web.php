<?php

use App\Modules\Announcements\Http\Controllers\AnnouncementsController;
use App\Modules\Announcements\Http\Controllers\PublicAnnouncementController;
use Illuminate\Support\Facades\Route;

// Routes of the Pengumuman module. Loaded with the "web" middleware group.
// Keep every route name under the "announcements." prefix.

Route::name('announcements.public.')->controller(PublicAnnouncementController::class)->group(function () {
    Route::get('/pengumuman', 'index')->name('index');
    Route::get('/pengumuman/{slug}', 'show')->where('slug', '[a-z0-9-]+')->name('show');
});

Route::middleware(['auth', 'verified'])
    ->prefix('admin/announcements')
    ->name('announcements.')
    ->controller(AnnouncementsController::class)
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('/{announcement}/edit', 'edit')->name('edit');
        Route::put('/{announcement}', 'update')->name('update');
        Route::delete('/{announcement}', 'destroy')->name('destroy');
    });
