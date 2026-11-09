<?php

use App\Modules\Landing\Http\Controllers\HomeController;
use App\Modules\Landing\Http\Controllers\LandingController;
use App\Modules\Landing\Http\Controllers\PublicPageController;
use App\Modules\Landing\Http\Controllers\SegmentController;
use App\Modules\Landing\Models\SiteSection;
use Illuminate\Support\Facades\Route;

// Routes of the Landing Page module. Loaded with the "web" middleware group.
// Keep every route name under the "landing." prefix, except the public
// homepage, which keeps the framework-wide name "home".

Route::get('/', HomeController::class)->name('home');

// One public page per navbar item (menu of ggpelim.com).
Route::name('landing.')->controller(PublicPageController::class)->group(function () {
    Route::get('/tentang', 'about')->name('about');
    Route::get('/renungan', 'devotional')->name('devotional');
    Route::get('/jadwal', 'schedule')->name('schedule');
    Route::get('/doa', 'prayer')->name('prayer');
    Route::get('/kontak', 'contact')->name('contact');
});

// TODO(core): add 'can:landing.manage' once the Core module's roles are
// merged; until then every verified account can edit the segments.
Route::middleware(['auth', 'verified'])
    ->prefix('admin/landing')
    ->name('landing.')
    ->group(function () {
        Route::get('/', [LandingController::class, 'index'])->name('index');
        Route::post('/segments/{segment}', [SegmentController::class, 'update'])
            ->whereIn('segment', SiteSection::SEGMENT_KEYS)
            ->name('segments.update');
    });
