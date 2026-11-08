<?php

namespace App\Modules\Members;

use App\Modules\Members\Database\Seeders\MembersSeeder;
use App\Support\Modules\ModuleServiceProvider;

class MembersServiceProvider extends ModuleServiceProvider
{
    public function key(): string
    {
        return 'members';
    }

    public function name(): string
    {
        return 'Jemaat';
    }

    public function navigation(): array
    {
        return [
            // Add 'permission' => 'members.view' once roles and permissions exist.
            ['title' => 'Jemaat', 'route' => 'members.index', 'icon' => 'index', 'group' => 'Administrasi', 'order' => 60],
        ];
    }

    public function permissions(): array
    {
        return [
            'members.view',
        ];
    }

    public function seeders(): array
    {
        return [
            MembersSeeder::class,
        ];
    }
}
