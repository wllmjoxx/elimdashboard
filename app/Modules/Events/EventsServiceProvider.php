<?php

namespace App\Modules\Events;

use App\Modules\Events\Database\Seeders\EventsSeeder;
use App\Modules\Events\Models\Event;
use App\Modules\Events\Policies\EventPolicy;
use App\Support\Modules\ModuleServiceProvider;
use Illuminate\Support\Facades\Gate;

class EventsServiceProvider extends ModuleServiceProvider
{
    public function key(): string
    {
        return 'events';
    }

    public function name(): string
    {
        return 'Kegiatan';
    }

    public function navigation(): array
    {
        return [
            ['title' => 'Kegiatan', 'route' => 'events.index', 'icon' => 'index', 'permission' => 'events.manage', 'group' => 'Konten', 'order' => 25],
        ];
    }

    public function permissions(): array
    {
        return [
            'events.manage',
        ];
    }

    public function dashboardCards(): array
    {
        return [
            [
                'key' => 'events.upcoming',
                'title' => 'Kegiatan akan datang',
                'description' => 'Sudah tampil di situs',
                'value' => fn () => Event::query()->published()->upcoming()->count(),
                'route' => 'events.index',
                'icon' => 'index',
                'permission' => 'events.manage',
                'order' => 50,
            ],
        ];
    }

    public function sitemap(): iterable
    {
        $events = Event::query()->published()->get(['slug', 'updated_at']);

        yield ['loc' => route('events.public.index'), 'lastmod' => $events->max('updated_at')];

        foreach ($events as $event) {
            yield ['loc' => route('events.public.show', $event->slug), 'lastmod' => $event->updated_at];
        }
    }

    public function seeders(): array
    {
        return [
            EventsSeeder::class,
        ];
    }

    protected function bootModule(): void
    {
        Gate::policy(Event::class, EventPolicy::class);
    }
}
