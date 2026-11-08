<?php

namespace App\Modules\Testimonies\Http\Controllers;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class TestimoniesController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('testimonies/index');
    }
}
