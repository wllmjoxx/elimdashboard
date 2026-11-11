<?php

namespace App\Modules\Gallery;

use App\Modules\Gallery\Database\Seeders\GallerySeeder;
use App\Modules\Gallery\Models\Album;
use App\Modules\Gallery\Policies\AlbumPolicy;
use App\Support\Modules\ModuleServiceProvider;
use Illuminate\Support\Facades\Gate;

class GalleryServiceProvider extends ModuleServiceProvider
{
    public function key(): string
    {
        return 'gallery';
    }

    public function name(): string
    {
        return 'Galeri';
    }

    public function navigation(): array
    {
        return [
            ['title' => 'Galeri', 'route' => 'gallery.index', 'icon' => 'index', 'permission' => 'gallery.view', 'group' => 'Konten', 'order' => 30],
        ];
    }

    public function permissions(): array
    {
        return [
            'gallery.view',
            'gallery.upload',
            'gallery.manage',
        ];
    }

    public function sitemap(): iterable
    {
        $albums = Album::query()->visible()->whereHas('activePhotos')->get(['id', 'slug', 'updated_at']);

        yield ['loc' => route('gallery.public.index'), 'lastmod' => $albums->max('updated_at')];

        foreach ($albums as $album) {
            yield ['loc' => route('gallery.public.show', $album->slug), 'lastmod' => $album->updated_at];
        }
    }

    public function seeders(): array
    {
        return [
            GallerySeeder::class,
        ];
    }

    protected function bootModule(): void
    {
        Gate::policy(Album::class, AlbumPolicy::class);
    }
}
