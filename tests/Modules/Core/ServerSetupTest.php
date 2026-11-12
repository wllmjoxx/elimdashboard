<?php

namespace Tests\Modules\Core;

use App\Models\User;
use App\Modules\Core\Database\Seeders\CoreSeeder;
use App\Modules\Core\Enums\Role;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ServerSetupTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_skips_dummy_accounts_on_servers(): void
    {
        foreach (['staging', 'production'] as $environment) {
            $this->app['env'] = $environment;

            // Called directly: db:seed itself asks for confirmation in production.
            $this->app->call([$this->app->make(CoreSeeder::class), 'run']);

            $this->assertDatabaseMissing('users', ['email' => 'test@example.com']);
            $this->assertDatabaseMissing('users', ['email' => 'admin@example.com']);
            $this->assertDatabaseHas('churches', ['slug' => 'ggp-elim']);
        }
    }

    public function test_servers_get_landing_content_but_no_sample_content(): void
    {
        $this->app['env'] = 'production';

        $this->app->make(DatabaseSeeder::class)->setContainer($this->app)->__invoke();

        $this->assertDatabaseHas('churches', ['slug' => 'ggp-elim']);
        $this->assertDatabaseHas('site_sections', ['key' => 'hero']);
        $this->assertDatabaseHas('service_schedules', ['name' => 'Ibadah Raya I']);

        foreach (['users', 'member_profiles', 'devotionals', 'albums', 'prayer_requests', 'testimonies', 'events', 'announcements'] as $table) {
            if (Schema::hasTable($table)) {
                $this->assertDatabaseCount($table, 0);
            }
        }
    }

    public function test_seeder_still_creates_dummy_accounts_locally(): void
    {
        $this->app['env'] = 'local';

        $this->seed(CoreSeeder::class);

        $this->assertTrue(User::query()->where('email', 'admin@example.com')->sole()->hasRole(Role::Admin->value));
    }

    public function test_command_creates_a_superadmin_with_a_typed_password(): void
    {
        $this->seed(CoreSeeder::class);

        $this->artisan('core:create-superadmin', ['email' => 'Gembala@Ggpelim.org', '--name' => 'Gembala'])
            ->expectsQuestion('Password (minimal 12 karakter)', 'rahasia-panjang-123')
            ->expectsQuestion('Ulangi password', 'rahasia-panjang-123')
            ->assertSuccessful();

        $user = User::query()->where('email', 'gembala@ggpelim.org')->sole();

        $this->assertTrue($user->hasRole(Role::Superadmin->value));
        $this->assertTrue(Hash::check('rahasia-panjang-123', $user->password));
        $this->assertNotNull($user->email_verified_at);
        $this->assertNotNull($user->church_id);
    }

    public function test_command_refuses_short_or_mismatched_passwords(): void
    {
        $this->artisan('core:create-superadmin', ['email' => 'a@ggpelim.org', '--name' => 'A'])
            ->expectsQuestion('Password (minimal 12 karakter)', 'pendek')
            ->expectsQuestion('Ulangi password', 'beda')
            ->assertFailed();

        $this->assertDatabaseMissing('users', ['email' => 'a@ggpelim.org']);
    }
}
