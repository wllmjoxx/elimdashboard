<?php

namespace App\Modules\Devotionals;

use App\Modules\Devotionals\Database\Seeders\DevotionalsSeeder;
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
