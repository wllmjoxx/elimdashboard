<?php

use App\Modules\Core\Http\Controllers\CoreController;
use Illuminate\Support\Facades\Route;

// Routes of the Cabang & Akses module. Loaded with the "web" middleware group.
// Keep every route name under the "core." prefix.

Route::middleware(['auth', 'verified', 'can:core.view'])
    ->prefix('admin/core')
    ->name('core.')
    ->group(function () {
        Route::get('/', [CoreController::class, 'index'])->name('index');
    });
