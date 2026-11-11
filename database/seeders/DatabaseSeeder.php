<?php

namespace Database\Seeders;

use App\Models\User;
use App\Support\Modules\ModuleRegistry;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // Known password: developer machines and tests only.
        if (app()->environment(['local', 'testing'])) {
            User::factory()->create([
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);
        }

        // Each module lists its own seeders in ModuleServiceProvider::seeders(),
        // so new modules never need to edit this file.
        $this->call(app(ModuleRegistry::class)->seeders());
    }
}
