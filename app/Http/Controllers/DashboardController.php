<?php

namespace App\Http\Controllers;

use App\Support\Modules\ModuleRegistry;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Dashboard (PRD 4.8): number cards contributed by the modules, limited to
 * what the signed-in user may see.
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request, ModuleRegistry $modules): Response
    {
        return Inertia::render('dashboard', [
            'cards' => $modules->dashboardCards($request->user()),
        ]);
    }
}
