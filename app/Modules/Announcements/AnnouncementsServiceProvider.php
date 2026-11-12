<?php

namespace App\Modules\Announcements;

use App\Modules\Announcements\Database\Seeders\AnnouncementsSeeder;
use App\Modules\Announcements\Models\Announcement;
use App\Modules\Announcements\Policies\AnnouncementPolicy;
use App\Support\Modules\ModuleServiceProvider;
use Illuminate\Support\Facades\Gate;

class AnnouncementsServiceProvider extends ModuleServiceProvider
{
    public function key(): string
    {
        return 'announcements';
    }

    public function name(): string
    {
        return 'Pengumuman';
    }

    public function navigation(): array
    {
        return [
            ['title' => 'Pengumuman', 'route' => 'announcements.index', 'icon' => 'index', 'permission' => 'announcements.manage', 'group' => 'Konten', 'order' => 26],
        ];
    }

    public function permissions(): array
    {
        return [
            'announcements.manage',
        ];
    }

    public function dashboardCards(): array
    {
        return [
            [
                'key' => 'announcements.live',
                'title' => 'Pengumuman tayang',
                'description' => 'Tampil di situs hari ini',
                'value' => fn () => Announcement::query()->live()->count(),
                'route' => 'announcements.index',
                'icon' => 'index',
                'permission' => 'announcements.manage',
                'order' => 55,
            ],
        ];
    }

    public function sitemap(): iterable
    {
        $announcements = Announcement::query()->live()->get(['slug', 'updated_at']);

        yield ['loc' => route('announcements.public.index'), 'lastmod' => $announcements->max('updated_at')];

        foreach ($announcements as $announcement) {
            yield ['loc' => route('announcements.public.show', $announcement->slug), 'lastmod' => $announcement->updated_at];
        }
    }

    public function seeders(): array
    {
        return [
            AnnouncementsSeeder::class,
        ];
    }

    protected function bootModule(): void
    {
        Gate::policy(Announcement::class, AnnouncementPolicy::class);
    }
}
