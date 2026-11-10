<?php

use App\Modules\Core\Http\Controllers\CoreController;
use App\Modules\Core\Http\Controllers\OtpLoginController;
use Illuminate\Support\Facades\Route;

// Routes of the Cabang & Akses module. Loaded with the "web" middleware group.
// Keep every route name under the "core." prefix.

// "Masuk dengan kode email" (PRD 4.4), for guests only.
Route::middleware('guest')
    ->prefix('login/kode')
    ->name('core.otp.')
    ->controller(OtpLoginController::class)
    ->group(function () {
        Route::get('/', 'create')->name('create');
        Route::post('/', 'store')->middleware('throttle:otp-send')->name('store');
        Route::get('/verifikasi', 'edit')->name('verify');
        Route::post('/verifikasi', 'update')->middleware('throttle:otp-verify')->name('update');
    });

Route::middleware(['auth', 'verified', 'can:core.view'])
    ->prefix('admin/core')
    ->name('core.')
    ->group(function () {
        Route::get('/', [CoreController::class, 'index'])->name('index');
    });
