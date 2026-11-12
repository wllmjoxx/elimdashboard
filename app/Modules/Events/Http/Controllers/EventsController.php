<?php

namespace App\Modules\Events\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Events\Http\Requests\SaveEventRequest;
use App\Modules\Events\Models\Event;
use App\Modules\Events\Support\EventFeed;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Admin › Kegiatan: events of the branch.
 */
class EventsController extends Controller
{
    public function __construct(private readonly EventFeed $feed) {}

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Event::class);

        $events = Event::query()->orderByDesc('starts_at')->get()->map(fn (Event $event) => [
            ...$this->feed->present($event),
            'isPublished' => $event->is_published,
        ]);

        return Inertia::render('events/index', [
            'events' => $events,
            'flash' => $request->session()->get('status'),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Event::class);

        return Inertia::render('events/form', ['event' => null]);
    }

    public function store(SaveEventRequest $request): RedirectResponse
    {
        Gate::authorize('create', Event::class);

        $event = new Event($request->safe()->except(['image', 'remove_image']));
        $event->is_published = $request->boolean('is_published', true);
        $event->created_by = $request->user()->id;
        $event->image_path = $this->storeImage($request);
        $event->save();

        return to_route('events.index')->with('status', "Kegiatan \"{$event->title}\" disimpan.");
    }

    public function edit(Event $event): Response
    {
        Gate::authorize('update', $event);

        return Inertia::render('events/form', [
            'event' => [
                'id' => $event->id,
                'title' => $event->title,
                'summary' => $event->summary ?? '',
                'body' => $event->body ?? '',
                'location' => $event->location ?? '',
                'startsAt' => $event->starts_at->format('Y-m-d\TH:i'),
                'endsAt' => $event->ends_at?->format('Y-m-d\TH:i') ?? '',
                'registrationUrl' => $event->registration_url ?? '',
                'isPublished' => $event->is_published,
                'imageUrl' => $event->imageUrl(),
                'url' => route('events.public.show', $event->slug),
            ],
        ]);
    }

    public function update(SaveEventRequest $request, Event $event): RedirectResponse
    {
        Gate::authorize('update', $event);

        $event->fill($request->safe()->except(['image', 'remove_image']));
        $event->is_published = $request->boolean('is_published', true);

        if ($request->boolean('remove_image') || $request->hasFile('image')) {
            $this->deleteImage($event);
            $event->image_path = $this->storeImage($request);
        }

        $event->save();

        return to_route('events.index')->with('status', "Kegiatan \"{$event->title}\" diperbarui.");
    }

    public function destroy(Event $event): RedirectResponse
    {
        Gate::authorize('delete', $event);

        $this->deleteImage($event);
        $event->delete();

        return to_route('events.index')->with('status', "Kegiatan \"{$event->title}\" dihapus.");
    }

    private function storeImage(SaveEventRequest $request): ?string
    {
        $config = config('modules.events.image');

        return $request->file('image')?->store($config['directory'], $config['disk']);
    }

    private function deleteImage(Event $event): void
    {
        if ($event->image_path) {
            Storage::disk((string) config('modules.events.image.disk'))->delete($event->image_path);
        }
    }
}
