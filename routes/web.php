<?php

use Illuminate\Support\Facades\Route;

// The public homepage ("/", route name "home") is defined by the Landing
// module (app/Modules/Landing/routes/web.php). Feature routes belong in their
// module, not here.

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
