<?php

namespace Tests\Modules\Core;

use App\Models\User;
use App\Modules\Core\Actions\SyncRolesAndPermissions;
use App\Modules\Core\Enums\Role;
use App\Modules\Core\Models\AuditLog;
use App\Modules\Core\Models\Church;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SuperadminPagesTest extends TestCase
{
    use RefreshDatabase;

    private Church $elim;

    protected function setUp(): void
    {
        parent::setUp();

        app(SyncRolesAndPermissions::class)->handle();
        $this->elim = Church::query()->create(['name' => 'GGP Elim', 'slug' => 'ggp-elim']);
    }

    public function test_only_superadmin_sees_the_audit_log(): void
    {
        $superadmin = $this->user(Role::Superadmin);
        $this->actingAs($superadmin);
        AuditLog::record('prayer.week.viewed', metadata: ['count' => 3]);
        AuditLog::record('members.invited', $this->elim);

        $this->get(route('core.audit'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('core/audit')
                ->has('logs.data', 2)
                ->where('logs.data.0.label', 'Mengundang akun staf')
                ->where('logs.data.0.subject', 'Church #'.$this->elim->id)
                ->where('logs.data.1.metadata', '{"count":3}')
                ->where('logs.data.1.user.name', $superadmin->name)
                ->has('actions', 2));

        $this->get(route('core.audit', ['action' => 'members.invited']))
            ->assertInertia(fn (Assert $page) => $page->has('logs.data', 1)->where('filters.action', 'members.invited'));

        $this->actingAs($this->user(Role::Admin))->get(route('core.audit'))->assertForbidden();
    }

    public function test_superadmin_adds_and_edits_branches(): void
    {
        $superadmin = $this->user(Role::Superadmin);

        $this->actingAs($superadmin)
            ->get(route('core.index'))
            ->assertInertia(fn (Assert $page) => $page->where('canManageChurches', true)->where('canViewAudit', true)->where('churches.0.isDefault', true));

        $this->actingAs($superadmin)
            ->post(route('core.churches.store'), [
                'name' => 'GGP Elim Bogor',
                'address' => 'Bogor',
                'whatsapp' => '0812 1111 2222',
                'timezone' => 'Asia/Jakarta',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $church = Church::query()->where('slug', 'ggp-elim-bogor')->firstOrFail();
        $this->assertSame('0812 1111 2222', $church->contact['whatsapp']);
        $this->assertTrue($church->is_active);

        $this->actingAs($superadmin)
            ->put(route('core.churches.update', $church), ['name' => 'GGP Elim Bogor', 'slug' => 'ggp-elim-bogor', 'timezone' => 'Asia/Jakarta', 'is_active' => false])
            ->assertSessionHasNoErrors();
        $this->assertFalse($church->refresh()->is_active);

        $this->assertSame(2, AuditLog::query()->where('action', 'like', 'core.church.%')->count());
    }

    public function test_branch_rules(): void
    {
        $superadmin = $this->user(Role::Superadmin);

        $this->actingAs($superadmin)
            ->post(route('core.churches.store'), ['name' => 'Elim', 'slug' => 'ggp-elim', 'timezone' => 'Europe/Paris'])
            ->assertSessionHasErrors(['slug', 'timezone']);

        $this->actingAs($superadmin)
            ->put(route('core.churches.update', $this->elim), ['name' => 'GGP Elim', 'slug' => 'ggp-elim', 'timezone' => 'Asia/Jakarta', 'is_active' => false])
            ->assertSessionHasErrors('is_active');
        $this->assertTrue($this->elim->refresh()->is_active);
    }

    public function test_admin_cannot_manage_branches(): void
    {
        $admin = $this->user(Role::Admin);

        $this->actingAs($admin)
            ->get(route('core.index'))
            ->assertInertia(fn (Assert $page) => $page->where('canManageChurches', false)->where('canViewAudit', false));

        $this->actingAs($admin)->post(route('core.churches.store'), ['name' => 'X', 'timezone' => 'Asia/Jakarta'])->assertForbidden();
        $this->actingAs($admin)->put(route('core.churches.update', $this->elim), ['name' => 'X', 'timezone' => 'Asia/Jakarta'])->assertForbidden();
    }

    private function user(Role $role): User
    {
        $user = User::factory()->create(['church_id' => $this->elim->id]);
        $user->assignRole($role->value);

        return $user;
    }
}
