<?php

namespace App\Modules\Testimonies\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Support\Captcha;
use App\Modules\Testimonies\Enums\TestimonyCategory;
use App\Modules\Testimonies\Http\Requests\StoreTestimonyRequest;
use App\Modules\Testimonies\Models\Testimony;
use App\Modules\Testimonies\Support\TestimonyFeed;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Public "Kesaksian" page (PRD 4.7): approved testimonies and the anonymous
 * form for visitors and members alike.
 */
class PublicTestimonyController extends Controller
{
    public function index(Request $request, TestimonyFeed $feed, Captcha $captcha): Response
    {
        $testimonies = Testimony::query()->approved()
            ->paginate((int) config('modules.testimonies.per_page'))
            ->through(fn (Testimony $testimony) => $feed->present($testimony));

        return Inertia::render('testimonies/public/index', [
            'testimonies' => $testimonies,
            'categories' => array_map(fn (TestimonyCategory $category) => [
                'value' => $category->value,
                'label' => $category->label(),
            ], TestimonyCategory::cases()),
            'captchaSiteKey' => $captcha->siteKey(),
            'confirmation' => $request->session()->get('testimony_sent'),
        ])->withViewData('seo', [
            'title' => 'Kesaksian',
            'description' => 'Kesaksian jemaat GGP Elim Ciputat tentang karya Tuhan dalam hidup mereka.',
        ]);
    }

    public function store(StoreTestimonyRequest $request): RedirectResponse
    {
        // Only the story itself is kept: no user, IP or session link.
        Testimony::query()->create($request->safe()->only(['title', 'body', 'category', 'display_name']));

        return to_route('testimonies.public.index')
            ->with('testimony_sent', 'Terima kasih sudah berbagi. Kesaksian Anda akan tampil setelah ditinjau Admin.');
    }
}
