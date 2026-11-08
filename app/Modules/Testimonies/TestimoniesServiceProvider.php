<?php

namespace App\Modules\Testimonies;

use App\Modules\Testimonies\Database\Seeders\TestimoniesSeeder;
use App\Support\Modules\ModuleServiceProvider;

class TestimoniesServiceProvider extends ModuleServiceProvider
{
    public function key(): string
    {
        return 'testimonies';
    }

    public function name(): string
    {
        return 'Kesaksian';
    }

    public function navigation(): array
    {
        return [
            // Add 'permission' => 'testimonies.view' once roles and permissions exist.
            ['title' => 'Kesaksian', 'route' => 'testimonies.index', 'icon' => 'index', 'group' => 'Pelayanan', 'order' => 50],
        ];
    }

    public function permissions(): array
    {
        return [
            'testimonies.view',
        ];
    }

    public function seeders(): array
    {
        return [
            TestimoniesSeeder::class,
        ];
    }
}
