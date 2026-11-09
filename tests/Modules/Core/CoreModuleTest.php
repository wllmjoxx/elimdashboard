<?php

namespace Tests\Modules\Core;

use App\Models\User;
use App\Modules\Core\Actions\SyncRolesAndPermissions;
use App\Modules\Core\Enums\Role;
use App\Modules\Core\Models\Church;
use App\Support\Modules\ModuleRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CoreModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(SyncRolesAndPermissions::class)->handle();
    }

    public function test_module_is_registered(): void
    {
        $this->assertNotNull(app(ModuleRegistry::class)->find('core'));
    }

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $this->get(route('core.index'))->assertRedirect(route('login'));
    }

    public function test_users_without_permission_cannot_open_the_index_page(): void
    {
        $this->actingAs($this->userWithRole(Role::Member))
            ->get(route('core.index'))
            ->assertForbidden();
    }

    public function test_admin_sees_only_their_own_branch(): void
    {
        $elim = Church::query()->create(['name' => 'GGP Elim', 'slug' => 'ggp-elim']);
        Church::query()->create(['name' => 'GGP Lain', 'slug' => 'ggp-lain']);

        $this->actingAs($this->userWithRole(Role::Admin, $elim))
            ->get(route('core.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('core/index')
                ->has('churches', 1)
                ->where('churches.0.name', 'GGP Elim')
                ->where('churches.0.usersCount', 1)
                ->has('roles', count(Role::cases())));
    }

    public function test_superadmin_sees_every_branch(): void
    {
        Church::query()->create(['name' => 'GGP Elim', 'slug' => 'ggp-elim']);
        Church::query()->create(['name' => 'GGP Lain', 'slug' => 'ggp-lain']);

        $this->actingAs($this->userWithRole(Role::Superadmin))
            ->get(route('core.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('churches', 2));
    }

    public function test_sidebar_item_is_hidden_without_permission(): void
    {
        $modules = app(ModuleRegistry::class);

        $this->assertNotContains('core', array_column($modules->navigation($this->userWithRole(Role::Member)), 'module'));
        $this->assertContains('core', array_column($modules->navigation($this->userWithRole(Role::Admin)), 'module'));
    }

    private function userWithRole(Role $role, ?Church $church = null): User
    {
        $user = User::factory()->create(['church_id' => $church?->id]);
        $user->assignRole($role->value);

        return $user;
    }
}
