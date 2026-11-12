<?php

namespace App\Modules\Events\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Events\Models\Event;
use App\Modules\Events\Support\EventFeed;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Public "Kegiatan" pages: what is happening now, what is coming, and the
 * most recent past events.
 */
class PublicEventController extends Controller
{
    public function __construct(private readonly EventFeed $feed) {}

    public function index(): Response
    {
        $present = fn (Event $event) => $this->feed->present($event);

        return Inertia::render('events/public/index', [
            'ongoing' => Event::query()->published()->ongoing()->get()->map($present)->values(),
            'upcoming' => Event::query()->published()->upcoming()->get()->map($present)->values(),
            'past' => Event::query()->published()->past()->limit((int) config('modules.events.past_limit'))->get()->map($present)->values(),
        ])->withViewData('seo', [
            'title' => 'Kegiatan',
            'description' => 'Kegiatan GGP Elim Ciputat yang sedang berjalan dan akan datang.',
        ]);
    }

    public function show(string $slug): Response
    {
        $event = Event::query()->published()->where('slug', $slug)->firstOrFail();

        return Inertia::render('events/public/show', [
            'event' => $this->feed->present($event, withBody: true),
        ])->withViewData('seo', [
            'title' => $event->title,
            'description' => $event->summary ?: "{$event->title} di GGP Elim Ciputat.",
            'image' => $event->imageUrl(),
        ]);
    }
}
