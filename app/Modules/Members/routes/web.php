<?php

use App\Modules\Members\Http\Controllers\MembersController;
use Illuminate\Support\Facades\Route;

// Routes of the Jemaat module. Loaded with the "web" middleware group.
// Keep every route name under the "members." prefix.

Route::middleware(['auth', 'verified'])
    ->prefix('admin/members')
    ->name('members.')
    ->group(function () {
        Route::get('/', [MembersController::class, 'index'])->name('index');
    });
