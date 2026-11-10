<?php

namespace App\Modules\Devotionals;

use App\Modules\Devotionals\Database\Seeders\DevotionalsSeeder;
use App\Modules\Devotionals\Enums\DevotionalStatus;
use App\Modules\Devotionals\Models\Devotional;
use App\Modules\Devotionals\Policies\DevotionalPolicy;
use App\Support\Modules\ModuleServiceProvider;
use Illuminate\Support\Facades\Gate;

class DevotionalsServiceProvider extends ModuleServiceProvider
{
    public function key(): string
    {
        return 'devotionals';
    }

    public function name(): string
    {
        return 'Renungan';
    }

    public function navigation(): array
    {
        return [
            ['title' => 'Renungan', 'route' => 'devotionals.index', 'icon' => 'index', 'permission' => 'devotionals.view', 'group' => 'Konten', 'order' => 20],
        ];
    }

    public function permissions(): array
    {
        return [
            'devotionals.view',
            'devotionals.write',
            'devotionals.publish',
        ];
    }

    public function dashboardCards(): array
    {
        return [
            [
                'key' => 'devotionals.drafts',
                'title' => 'Draf renungan',
                'description' => 'Menunggu ditinjau dan diterbitkan',
                'value' => fn () => Devotional::query()->where('status', DevotionalStatus::Draft->value)->count(),
                'route' => 'devotionals.index',
                'query' => ['tab' => 'draft'],
                'icon' => 'index',
                'permission' => 'devotionals.publish',
                'highlight' => true,
                'order' => 40,
            ],
            [
                'key' => 'devotionals.scheduled',
                'title' => 'Renungan terjadwal',
                'description' => 'Sudah disetujui, terbit di tanggalnya',
                'value' => fn () => Devotional::query()
                    ->where('status', DevotionalStatus::Published->value)
                    ->whereDate('publish_date', '>', Devotional::today()->toDateString())
                    ->count(),
                'route' => 'devotionals.index',
                'query' => ['tab' => 'scheduled'],
                'icon' => 'index',
                'permission' => 'devotionals.view',
                'order' => 45,
            ],
        ];
    }

    public function seeders(): array
    {
        return [
            DevotionalsSeeder::class,
        ];
    }

    protected function bootModule(): void
    {
        Gate::policy(Devotional::class, DevotionalPolicy::class);
    }
}
