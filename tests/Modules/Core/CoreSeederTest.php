<?php

namespace Tests\Modules\Core;

use App\Models\User;
use App\Modules\Core\Database\Seeders\CoreSeeder;
use App\Modules\Core\Enums\Role;
use App\Modules\Core\Models\Church;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CoreSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_the_default_branch_and_one_account_per_role(): void
    {
        $this->seed(CoreSeeder::class);

        $church = Church::default();
        $this->assertNotNull($church);
        $this->assertSame('GGP Jemaat Elim Ciputat', $church->name);

        foreach (Role::cases() as $role) {
            $this->assertSame(1, User::role($role->value)->count(), $role->value);
        }

        $this->assertSame(count(Role::cases()), $church->users()->count());
        $this->assertTrue(User::query()->where('email', 'test@example.com')->firstOrFail()->hasRole(Role::Superadmin->value));
    }

    public function test_it_can_run_twice(): void
    {
        $this->seed(CoreSeeder::class);
        $this->seed(CoreSeeder::class);

        $this->assertSame(1, Church::query()->count());
        $this->assertSame(count(Role::cases()), User::query()->count());
    }

    public function test_it_runs_inside_the_database_seeder(): void
    {
        // DatabaseSeeder switches model events off, which spatie relies on.
        $this->seed();

        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();

        $this->assertTrue($admin->hasRole(Role::Admin->value));
        $this->assertTrue($admin->can('core.view'));
    }

    public function test_users_have_a_church_relation(): void
    {
        $this->seed(CoreSeeder::class);

        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();

        $this->assertTrue($admin->church->is(Church::default()));
    }
}
