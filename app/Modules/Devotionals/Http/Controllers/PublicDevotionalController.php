<?php

namespace App\Modules\Devotionals\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Devotionals\Models\Devotional;
use App\Modules\Devotionals\Support\DevotionalFeed;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Public "Renungan Harian" pages (PRD 4.3): today's devotional with the
 * archive below it, and a page per devotional.
 */
class PublicDevotionalController extends Controller
{
    public function __construct(private readonly DevotionalFeed $feed) {}

    public function index(): Response
    {
        $archive = Devotional::query()->live()
            ->paginate((int) config('modules.devotionals.archive_per_page'))
            ->through(fn (Devotional $devotional) => $this->feed->present($devotional));

        $latest = $archive->onFirstPage() ? Devotional::query()->live()->first() : null;

        return Inertia::render('devotionals/public/index', [
            'latest' => $latest ? $this->feed->present($latest, withBody: true) : null,
            'archive' => $archive,
        ]);
    }

    public function show(string $slug): Response
    {
        $devotional = Devotional::query()->live()->where('slug', $slug)->firstOrFail();

        $neighbours = fn (string $operator, string $direction) => Devotional::query()->live()
            ->reorder()
            ->where(fn ($query) => $query
                ->whereDate('publish_date', $operator, $devotional->publish_date->toDateString())
                ->orWhere(fn ($query) => $query
                    ->whereDate('publish_date', $devotional->publish_date->toDateString())
                    ->where('id', $operator, $devotional->id)))
            ->orderBy('publish_date', $direction)
            ->orderBy('id', $direction)
            ->first();

        $newer = $neighbours('>', 'asc');
        $older = $neighbours('<', 'desc');

        return Inertia::render('devotionals/public/show', [
            'devotional' => $this->feed->present($devotional, withBody: true),
            'newer' => $newer ? $this->feed->present($newer) : null,
            'older' => $older ? $this->feed->present($older) : null,
        ]);
    }
}
