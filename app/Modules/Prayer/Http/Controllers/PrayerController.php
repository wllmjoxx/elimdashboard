<?php

namespace App\Modules\Prayer\Http\Controllers;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class PrayerController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('prayer/index');
    }
}
