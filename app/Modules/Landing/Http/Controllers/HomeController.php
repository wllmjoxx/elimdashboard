<?php

namespace App\Modules\Landing\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Landing\Models\SiteSection;
use App\Modules\Landing\Support\LandingContent;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    /**
     * Public homepage: hero slider, the three admin-managed segments and
     * teasers that link to the other pages.
     */
    public function __invoke(LandingContent $content): Response
    {
        return Inertia::render('landing/public/home', [
            'sections' => $content->sections(SiteSection::HOME_KEYS),
            'scheduleGroups' => $content->scheduleGroups(),
        ])->withViewData('seo', ['schema' => true]);
    }
}
