<?php

namespace App\Modules\Devotionals;

use App\Modules\Devotionals\Database\Seeders\DevotionalsSeeder;
use App\Support\Modules\ModuleServiceProvider;

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
            // Add 'permission' => 'devotionals.view' once roles and permissions exist.
            ['title' => 'Renungan', 'route' => 'devotionals.index', 'icon' => 'index', 'group' => 'Konten', 'order' => 20],
        ];
    }

    public function permissions(): array
    {
        return [
            'devotionals.view',
        ];
    }

    public function seeders(): array
    {
        return [
            DevotionalsSeeder::class,
        ];
    }
}
