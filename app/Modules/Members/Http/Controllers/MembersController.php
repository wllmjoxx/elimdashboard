<?php

namespace App\Modules\Members\Http\Controllers;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class MembersController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('members/index');
    }
}
