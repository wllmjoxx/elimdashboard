<?php

use App\Modules\Landing\Http\Controllers\GallerySettingsController;
use App\Modules\Landing\Http\Controllers\HomeController;
use App\Modules\Landing\Http\Controllers\LandingController;
use App\Modules\Landing\Http\Controllers\PublicPageController;
use App\Modules\Landing\Http\Controllers\ScheduleController;
use App\Modules\Landing\Http\Controllers\SectionController;
use App\Modules\Landing\Http\Controllers\SegmentController;
use App\Modules\Landing\Http\Controllers\SeoController;
use App\Modules\Landing\Models\SiteSection;
use App\Modules\Landing\Support\SectionEditor;
use Illuminate\Support\Facades\Route;

// Routes of the Landing Page module. Loaded with the "web" middleware group.
// Keep every route name under the "landing." prefix, except the public
// homepage, which keeps the framework-wide name "home".

Route::get('/', HomeController::class)->name('home');

// One public page per navbar item (menu of ggpelim.com).
Route::name('landing.')->controller(PublicPageController::class)->group(function () {
    Route::get('/tentang', 'about')->name('about');
    Route::get('/jadwal', 'schedule')->name('schedule');
    Route::get('/kontak', 'contact')->name('contact');
    Route::get('/kebijakan-privasi', 'privacy')->name('privacy');
});

// Search engines (pages from every module) and crawler rules.
Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('landing.sitemap');
Route::get('/robots.txt', [SeoController::class, 'robots'])->name('landing.robots');

// Editing the landing page is for Admin and Superadmin (PRD 4.2).
Route::middleware(['auth', 'verified', 'can:landing.manage'])
    ->prefix('admin/landing')
    ->name('landing.')
    ->group(function () {
        // Layout: order, visibility and the text of each section.
        Route::get('/', [SectionController::class, 'index'])->name('index');
        Route::post('/sections/order', [SectionController::class, 'reorder'])->name('sections.order');
        Route::get('/sections/{section}', [SectionController::class, 'edit'])
            ->whereIn('section', SectionEditor::EDITABLE)
            ->name('sections.edit');
        Route::put('/sections/{section}', [SectionController::class, 'update'])
            ->whereIn('section', SectionEditor::EDITABLE)
            ->name('sections.update');
        Route::post('/sections/{section}/visibility', [SectionController::class, 'visibility'])
            ->whereIn('section', SiteSection::KEYS)
            ->name('sections.visibility');

        // Image segments and the homepage gallery placement.
        Route::get('/segmen', [LandingController::class, 'index'])->name('segments.edit');
        Route::post('/gallery', [GallerySettingsController::class, 'update'])->name('gallery.update');
        Route::post('/segments/{segment}', [SegmentController::class, 'update'])
            ->whereIn('segment', SiteSection::SEGMENT_KEYS)
            ->name('segments.update');

        // Worship schedule.
        Route::get('/jadwal', [ScheduleController::class, 'index'])->name('schedule.index');
        Route::post('/jadwal', [ScheduleController::class, 'store'])->name('schedule.store');
        Route::put('/jadwal/{schedule}', [ScheduleController::class, 'update'])->name('schedule.update');
        Route::delete('/jadwal/{schedule}', [ScheduleController::class, 'destroy'])->name('schedule.destroy');
    });
