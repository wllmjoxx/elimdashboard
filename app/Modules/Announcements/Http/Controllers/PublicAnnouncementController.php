<?php

namespace App\Modules\Announcements\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Announcements\Models\Announcement;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Public "Pengumuman" pages: current announcements, pinned first.
 */
class PublicAnnouncementController extends Controller
{
    public function index(): Response
    {
        $announcements = Announcement::query()->live()
            ->paginate((int) config('modules.announcements.per_page'))
            ->through(fn (Announcement $announcement) => $this->present($announcement));

        return Inertia::render('announcements/public/index', [
            'announcements' => $announcements,
        ])->withViewData('seo', [
            'title' => 'Pengumuman',
            'description' => 'Pengumuman terbaru dari GGP Elim Ciputat.',
        ]);
    }

    public function show(string $slug): Response
    {
        $announcement = Announcement::query()->live()->where('slug', $slug)->firstOrFail();

        return Inertia::render('announcements/public/show', [
            'announcement' => [...$this->present($announcement), 'body' => $announcement->body],
        ])->withViewData('seo', [
            'title' => $announcement->title,
            'description' => $announcement->excerpt(160),
            'type' => 'article',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function present(Announcement $announcement): array
    {
        return [
            'id' => $announcement->id,
            'title' => $announcement->title,
            'excerpt' => $announcement->excerpt(),
            'isPinned' => $announcement->is_pinned,
            'date' => $announcement->published_on->toDateString(),
            'dateLabel' => $announcement->published_on->locale('id')->translatedFormat('l, j F Y'),
            'url' => route('announcements.public.show', $announcement->slug),
        ];
    }
}
