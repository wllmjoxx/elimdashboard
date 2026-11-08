<?php

namespace App\Modules\Core;

use App\Modules\Core\Database\Seeders\CoreSeeder;
use App\Support\Modules\ModuleServiceProvider;

class CoreServiceProvider extends ModuleServiceProvider
{
    public function key(): string
    {
        return 'core';
    }

    public function name(): string
    {
        return 'Cabang & Akses';
    }

    public function navigation(): array
    {
        return [
            // Add 'permission' => 'core.view' once roles and permissions exist.
            ['title' => 'Cabang & Akses', 'route' => 'core.index', 'icon' => 'index', 'group' => 'Administrasi', 'order' => 90],
        ];
    }

    public function permissions(): array
    {
        return [
            'core.view',
        ];
    }

    public function seeders(): array
    {
        return [
            CoreSeeder::class,
        ];
    }
}
