<?php

namespace App\Modules\Landing;

use App\Modules\Landing\Database\Seeders\LandingSeeder;
use App\Support\Modules\ModuleServiceProvider;

class LandingServiceProvider extends ModuleServiceProvider
{
    public function key(): string
    {
        return 'landing';
    }

    public function name(): string
    {
        return 'Landing Page';
    }

    public function navigation(): array
    {
        return [
            // Add 'permission' => 'landing.view' once roles and permissions exist.
            ['title' => 'Landing Page', 'route' => 'landing.index', 'icon' => 'index', 'group' => 'Konten', 'order' => 10],
        ];
    }

    public function permissions(): array
    {
        return [
            'landing.view',
            'landing.manage',
        ];
    }

    public function seeders(): array
    {
        return [
            LandingSeeder::class,
        ];
    }
}
