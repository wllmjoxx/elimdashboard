<?php

namespace App\Modules\Prayer;

use App\Modules\Prayer\Database\Seeders\PrayerSeeder;
use App\Support\Modules\ModuleServiceProvider;

class PrayerServiceProvider extends ModuleServiceProvider
{
    public function key(): string
    {
        return 'prayer';
    }

    public function name(): string
    {
        return 'Doa Mingguan';
    }

    public function navigation(): array
    {
        return [
            // Add 'permission' => 'prayer.view' once roles and permissions exist.
            ['title' => 'Doa Mingguan', 'route' => 'prayer.index', 'icon' => 'index', 'group' => 'Pelayanan', 'order' => 40],
        ];
    }

    public function permissions(): array
    {
        return [
            'prayer.view',
        ];
    }

    public function seeders(): array
    {
        return [
            PrayerSeeder::class,
        ];
    }
}
