<?php

namespace Tests\Modules\Core;

use App\Models\User;
use App\Modules\Core\Actions\SyncRolesAndPermissions;
use App\Modules\Core\Enums\Role;
use App\Support\Modules\ModuleRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role as SpatieRole;
use Tests\TestCase;

class RolesAndPermissionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_creates_every_module_permission_and_role(): void
    {
        app(SyncRolesAndPermissions::class)->handle();

        $this->assertEqualsCanonicalizing(
            app(ModuleRegistry::class)->permissions(),
            Permission::query()->pluck('name')->all(),
        );
        $this->assertEqualsCanonicalizing(
            array_column(Role::cases(), 'value'),
            SpatieRole::query()->pluck('name')->all(),
        );
    }

    public function test_roles_get_the_permissions_from_the_matrix(): void
    {
        app(SyncRolesAndPermissions::class)->handle();

        $admin = $this->userWithRole(Role::Admin);
        $prayerTeam = $this->userWithRole(Role::PrayerTeam);
        $member = $this->userWithRole(Role::Member);

        $this->assertTrue($admin->can('core.view'));
        $this->assertTrue($admin->can('prayer.view'));
        $this->assertFalse($admin->can('core.churches.manage'));

        $this->assertTrue($prayerTeam->can('prayer.view'));
        $this->assertFalse($prayerTeam->can('members.view'));

        $this->assertFalse($member->can('prayer.view'));
    }

    public function test_superadmin_passes_every_check(): void
    {
        app(SyncRolesAndPermissions::class)->handle();

        $superadmin = $this->userWithRole(Role::Superadmin);

        $this->assertTrue($superadmin->can('core.churches.manage'));
        $this->assertTrue($superadmin->can('permission.no-module-declares'));
    }

    public function test_sync_is_idempotent_and_resets_changed_roles(): void
    {
        $sync = app(SyncRolesAndPermissions::class);
        $sync->handle();

        SpatieRole::findByName(Role::PrayerTeam->value)->givePermissionTo('members.view');

        $sync->handle();

        $this->assertSame(count(app(ModuleRegistry::class)->permissions()), Permission::query()->count());
        $this->assertFalse(SpatieRole::findByName(Role::PrayerTeam->value)->hasPermissionTo('members.view'));
    }

    public function test_command_prints_the_roles(): void
    {
        $this->artisan('core:sync-permissions')
            ->expectsOutputToContain('Tim Doa')
            ->assertSuccessful();
    }

    private function userWithRole(Role $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role->value);

        return $user;
    }
}
