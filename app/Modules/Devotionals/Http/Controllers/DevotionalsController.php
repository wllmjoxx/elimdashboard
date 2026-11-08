<?php

namespace App\Modules\Devotionals\Http\Controllers;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class DevotionalsController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('devotionals/index');
    }
}
