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
            ['title' => 'Landing Page', 'route' => 'landing.index', 'icon' => 'index', 'permission' => 'landing.manage', 'group' => 'Konten', 'order' => 10],
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
