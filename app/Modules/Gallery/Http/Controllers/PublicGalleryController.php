<?php

namespace App\Modules\Gallery\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Gallery\Models\Album;
use App\Modules\Gallery\Models\AlbumPhoto;
use App\Modules\Gallery\Support\GalleryFeed;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Public gallery pages: active albums and the photos of one album.
 */
class PublicGalleryController extends Controller
{
    public function __construct(private readonly GalleryFeed $feed) {}

    public function index(): Response
    {
        $albums = Album::query()->visible()
            ->whereHas('activePhotos')
            ->with('cover')
            ->withCount('activePhotos')
            ->paginate(12)
            ->through(fn (Album $album) => $this->feed->album($album, $album->active_photos_count));

        return Inertia::render('gallery/public/index', ['albums' => $albums])->withViewData('seo', [
            'title' => 'Galeri',
            'description' => 'Album foto kegiatan GGP Elim Ciputat.',
        ]);
    }

    public function show(string $slug): Response
    {
        $album = Album::query()->visible()->where('slug', $slug)->firstOrFail();
        $photos = $album->activePhotos()->get();

        return Inertia::render('gallery/public/show', [
            'album' => $this->feed->album($album, $photos->count()),
            'photos' => $photos->map(fn (AlbumPhoto $photo) => $this->feed->photo($photo))->values(),
        ])->withViewData('seo', [
            'title' => $album->title,
            'description' => $album->description ?: "Foto {$album->title} GGP Elim Ciputat.",
            'image' => $photos->isNotEmpty() ? $this->feed->photo($photos->first())['url'] : null,
        ]);
    }
}
