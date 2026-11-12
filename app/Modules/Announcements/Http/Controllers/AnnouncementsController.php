<?php

namespace App\Modules\Announcements\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Announcements\Models\Announcement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Admin › Pengumuman: announcements of the branch.
 */
class AnnouncementsController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Announcement::class);

        $announcements = Announcement::query()
            ->orderByDesc('is_pinned')
            ->orderByDesc('published_on')
            ->orderByDesc('id')
            ->get()
            ->map(fn (Announcement $announcement) => [
                'id' => $announcement->id,
                'title' => $announcement->title,
                'excerpt' => $announcement->excerpt(140),
                'isPinned' => $announcement->is_pinned,
                'state' => $announcement->stateLabel(),
                'publishedLabel' => $announcement->published_on->locale('id')->translatedFormat('j M Y'),
                'expiresLabel' => $announcement->expires_on?->locale('id')->translatedFormat('j M Y'),
                'url' => route('announcements.public.show', $announcement->slug),
            ]);

        return Inertia::render('announcements/index', [
            'announcements' => $announcements,
            'flash' => $request->session()->get('status'),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Announcement::class);

        return Inertia::render('announcements/form', [
            'announcement' => null,
            'today' => Announcement::today()->toDateString(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Announcement::class);

        $announcement = new Announcement($this->validated($request));
        $announcement->created_by = $request->user()->id;
        $announcement->save();

        return to_route('announcements.index')->with('status', "Pengumuman \"{$announcement->title}\" disimpan.");
    }

    public function edit(Announcement $announcement): Response
    {
        Gate::authorize('update', $announcement);

        return Inertia::render('announcements/form', [
            'announcement' => [
                'id' => $announcement->id,
                'title' => $announcement->title,
                'body' => $announcement->body,
                'isPinned' => $announcement->is_pinned,
                'isPublished' => $announcement->is_published,
                'publishedOn' => $announcement->published_on->toDateString(),
                'expiresOn' => $announcement->expires_on?->toDateString() ?? '',
                'url' => route('announcements.public.show', $announcement->slug),
            ],
            'today' => Announcement::today()->toDateString(),
        ]);
    }

    public function update(Request $request, Announcement $announcement): RedirectResponse
    {
        Gate::authorize('update', $announcement);

        $announcement->update($this->validated($request));

        return to_route('announcements.index')->with('status', "Pengumuman \"{$announcement->title}\" diperbarui.");
    }

    public function destroy(Announcement $announcement): RedirectResponse
    {
        Gate::authorize('delete', $announcement);

        $announcement->delete();

        return to_route('announcements.index')->with('status', "Pengumuman \"{$announcement->title}\" dihapus.");
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'body' => ['required', 'string', 'max:10000'],
            'is_pinned' => ['boolean'],
            'is_published' => ['boolean'],
            'published_on' => ['required', 'date'],
            'expires_on' => ['nullable', 'date', 'after_or_equal:published_on'],
        ], [
            'expires_on.after_or_equal' => 'Tanggal berakhir tidak boleh sebelum tanggal tayang.',
        ], [
            'title' => 'judul',
            'body' => 'isi',
            'published_on' => 'tanggal tayang',
            'expires_on' => 'tanggal berakhir',
        ]);

        return [
            ...$data,
            'is_pinned' => $request->boolean('is_pinned'),
            'is_published' => $request->boolean('is_published', true),
        ];
    }
}
