<?php

use App\Modules\Members\Http\Controllers\MemberProfileController;
use App\Modules\Members\Http\Controllers\MembersController;
use App\Modules\Members\Http\Controllers\ProfileController;
use App\Modules\Members\Http\Controllers\RegisterController;
use App\Modules\Members\Http\Controllers\SetPasswordController;
use Illuminate\Support\Facades\Route;

// Routes of the Jemaat module. Loaded with the "web" middleware group.
// Keep every route name under the "members." prefix, except "register" and
// "register.store", which keep the names Fortify used so existing links work.

Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('register.store');
});

// "Buat password" link from the approval email (signed, expiring).
Route::middleware(['signed', 'throttle:6,1'])
    ->controller(SetPasswordController::class)
    ->prefix('set-password/{member}')
    ->name('members.password.')
    ->group(function () {
        Route::get('/', 'create')->name('create');
        Route::post('/', 'store')->name('store');
    });

// The signed-in member's own profile (PRD 4.5).
Route::middleware(['auth', 'verified'])
    ->prefix('profil')
    ->name('members.profile.')
    ->controller(ProfileController::class)
    ->group(function () {
        Route::get('/', 'edit')->name('edit');
        Route::post('/', 'update')->name('update');
        Route::post('/deletion', 'requestDeletion')->name('deletion');
        Route::delete('/deletion', 'cancelDeletion')->name('deletion.cancel');
    });

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/member-photos/{member}', [MemberProfileController::class, 'photo'])->name('members.photo');
    Route::get('/admin/members/{member}', [MemberProfileController::class, 'show'])->whereNumber('member')->name('members.show');
    Route::post('/admin/members/{member}/membership', [MemberProfileController::class, 'updateMembership'])->name('members.membership');
});

Route::middleware(['auth', 'verified'])
    ->prefix('admin/members')
    ->name('members.')
    ->controller(MembersController::class)
    ->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/{member}/approve', 'approve')->name('approve');
        Route::post('/{member}/reject', 'reject')->name('reject');
        Route::post('/{member}/request-info', 'requestInfo')->name('request-info');
        Route::post('/{member}/status', 'updateStatus')->name('status');
    });
