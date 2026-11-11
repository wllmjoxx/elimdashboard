<?php

namespace App\Modules\Gallery\Support;

use App\Modules\Gallery\Models\Album;
use App\Modules\Gallery\Models\AlbumPhoto;
use App\Modules\Gallery\Models\GallerySetting;

/**
 * Albums and photos in the shape public pages render. The Landing homepage
 * and its admin page read the gallery through this class.
 */
class GalleryFeed
{
    /**
     * Photos for the homepage section: from the chosen album, or the newest
     * album that has photos.
     *
     * @return array{album: array<string, mixed>|null, photos: list<array<string, mixed>>}
     */
    public function forHome(?int $albumId): array
    {
        $setting = GallerySetting::current();

        // Instagram mode: the newest posts, linking to the account.
        if ($setting->usesInstagram()) {
            $posts = array_slice(app(InstagramFeed::class)->posts($setting), 0, (int) config('modules.gallery.home_limit'));

            return [
                'album' => $posts === [] ? null : [
                    'title' => '@'.$setting->instagram_username,
                    'url' => 'https://www.instagram.com/'.$setting->instagram_username.'/',
                ],
                'photos' => array_map(fn (array $post) => [
                    'id' => $post['id'],
                    'url' => $post['image'],
                    'thumbnailUrl' => $post['image'],
                    'caption' => $post['caption'],
                    'width' => null,
                    'height' => null,
                ], $posts),
            ];
        }

        $album = $albumId
            ? Album::query()->visible()->whereKey($albumId)->first()
            : null;

        $album ??= Album::query()->visible()->whereHas('activePhotos')->first();

        if (! $album) {
            return ['album' => null, 'photos' => []];
        }

        $photos = $album->activePhotos()->limit((int) config('modules.gallery.home_limit'))->get();

        return [
            'album' => $this->album($album),
            'photos' => $photos->map(fn (AlbumPhoto $photo) => $this->photo($photo))->values()->all(),
        ];
    }

    /**
     * Albums Admin can pick for the homepage, with a few thumbnails for the
     * preview.
     *
     * @return list<array<string, mixed>>
     */
    public function picker(): array
    {
        return Album::query()->visible()->with(['activePhotos' => fn ($query) => $query->limit(4)])->get()
            ->map(fn (Album $album) => [
                'id' => $album->id,
                'title' => $album->title,
                'thumbnails' => $album->activePhotos->map(fn (AlbumPhoto $photo) => $photo->thumbnailUrl())->values()->all(),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function album(Album $album, ?int $photoCount = null): array
    {
        return [
            'id' => $album->id,
            'title' => $album->title,
            'slug' => $album->slug,
            'description' => $album->description,
            'eventDate' => $album->event_date?->toDateString(),
            'eventDateLabel' => $album->event_date?->locale('id')->translatedFormat('j F Y'),
            'url' => route('gallery.public.show', $album->slug),
            'coverUrl' => $album->relationLoaded('cover') ? $album->cover?->thumbnailUrl() : null,
            'photoCount' => $photoCount,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function photo(AlbumPhoto $photo): array
    {
        return [
            'id' => $photo->id,
            'url' => $photo->url(),
            'thumbnailUrl' => $photo->thumbnailUrl(),
            'caption' => $photo->caption,
            'width' => $photo->width,
            'height' => $photo->height,
        ];
    }
}
