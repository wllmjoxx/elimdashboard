<?php

namespace App\Modules\Gallery;

use App\Modules\Gallery\Database\Seeders\GallerySeeder;
use App\Support\Modules\ModuleServiceProvider;

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
            // Add 'permission' => 'gallery.view' once roles and permissions exist.
            ['title' => 'Galeri', 'route' => 'gallery.index', 'icon' => 'index', 'group' => 'Konten', 'order' => 30],
        ];
    }

    public function permissions(): array
    {
        return [
            'gallery.view',
        ];
    }

    public function seeders(): array
    {
        return [
            GallerySeeder::class,
        ];
    }
}
