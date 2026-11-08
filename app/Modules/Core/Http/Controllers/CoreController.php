<?php

namespace App\Modules\Core\Http\Controllers;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class CoreController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('core/index');
    }
}
