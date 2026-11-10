<?php

namespace Tests\Modules\Members;

use App\Models\User;
use App\Modules\Core\Actions\SyncRolesAndPermissions;
use App\Modules\Core\Enums\Role;
use App\Modules\Core\Models\AuditLog;
use App\Modules\Core\Models\Church;
use App\Modules\Members\Enums\MemberStatus;
use App\Modules\Members\Models\Member;
use App\Modules\Members\Notifications\StaffInvitation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class StaffTest extends TestCase
{
    use RefreshDatabase;

    private Church $elim;

    private Church $other;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        app(SyncRolesAndPermissions::class)->handle();
        $this->elim = Church::query()->create(['name' => 'GGP Elim', 'slug' => 'ggp-elim']);
        $this->other = Church::query()->create(['name' => 'GGP Lain', 'slug' => 'ggp-lain']);
    }

    public function test_admin_makes_a_member_servant_and_it_is_logged(): void
    {
        $admin = $this->user(Role::Admin);
        $member = $this->user(Role::Member);

        $this->actingAs($admin)
            ->get(route('members.show', $member->id))
            ->assertInertia(fn (Assert $page) => $page
                ->where('can.assignRole', true)
                ->where('role.current', 'member')
                ->has('role.options', 3));

        $this->actingAs($admin)->post(route('members.role', $member->id), ['role' => 'servant'])->assertRedirect();

        $this->assertTrue($member->fresh()->hasRole(Role::Servant->value));
        $this->assertFalse($member->fresh()->hasRole(Role::Member->value));

        $log = AuditLog::query()->where('action', 'members.role.changed')->firstOrFail();
        $this->assertSame(['from' => 'member', 'to' => 'servant'], $log->metadata);
        $this->assertSame($admin->id, $log->user_id);
    }

    public function test_admin_cannot_hand_out_admin_or_touch_admins(): void
    {
        $admin = $this->user(Role::Admin);
        $member = $this->user(Role::Member);
        $otherAdmin = $this->user(Role::Admin);

        $this->actingAs($admin)->post(route('members.role', $member->id), ['role' => 'admin'])->assertSessionHasErrors('role');
        $this->actingAs($admin)->post(route('members.role', $otherAdmin->id), ['role' => 'member'])->assertForbidden();
        $this->actingAs($admin)->post(route('members.role', $admin->id), ['role' => 'member'])->assertForbidden();
    }

    public function test_admin_cannot_deactivate_admins_or_superadmins(): void
    {
        $admin = $this->user(Role::Admin);
        $superadmin = $this->user(Role::Superadmin);
        $otherAdmin = $this->user(Role::Admin);

        foreach ([$superadmin, $otherAdmin] as $target) {
            $this->actingAs($admin)
                ->post(route('members.status', $target->id), ['status' => 'inactive'])
                ->assertForbidden();
        }

        $this->actingAs($admin)
            ->get(route('members.index', ['status' => 'active']))
            ->assertInertia(function (Assert $page) use ($superadmin) {
                $row = collect($page->toArray()['props']['members']['data'])->firstWhere('id', $superadmin->id);

                $this->assertFalse($row['can']['deactivate']);
            });

        $this->actingAs($superadmin)
            ->post(route('members.status', $otherAdmin->id), ['status' => 'inactive'])
            ->assertRedirect();
    }

    public function test_admin_cannot_change_roles_in_another_branch(): void
    {
        $outsider = $this->user(Role::Member, $this->other);

        $this->actingAs($this->user(Role::Admin))
            ->post(route('members.role', $outsider->id), ['role' => 'servant'])
            ->assertForbidden();
    }

    public function test_superadmin_hands_out_admin_but_never_changes_own_role(): void
    {
        $superadmin = $this->user(Role::Superadmin);
        $member = $this->user(Role::Member, $this->other);

        $this->actingAs($superadmin)->post(route('members.role', $member->id), ['role' => 'admin'])->assertRedirect();
        $this->assertTrue($member->fresh()->hasRole(Role::Admin->value));

        $this->actingAs($superadmin)->post(route('members.role', $superadmin->id), ['role' => 'member'])->assertForbidden();
        $this->assertTrue($superadmin->fresh()->hasRole(Role::Superadmin->value));
    }

    public function test_roles_without_permission_cannot_change_roles(): void
    {
        $member = $this->user(Role::Member);

        $this->actingAs($this->user(Role::PrayerTeam))
            ->post(route('members.role', $member->id), ['role' => 'servant'])
            ->assertForbidden();
    }

    public function test_admin_invites_staff_into_their_branch(): void
    {
        $admin = $this->user(Role::Admin);

        $this->actingAs($admin)
            ->get(route('members.index'))
            ->assertInertia(fn (Assert $page) => $page->has('invite.roles', 3)->where('invite.churches', []));

        $this->actingAs($admin)
            ->post(route('members.invite'), ['name' => 'Pelayan Baru', 'email' => 'pelayan.baru@example.com', 'role' => 'servant'])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $invited = Member::query()->where('email', 'pelayan.baru@example.com')->firstOrFail();
        $this->assertSame(MemberStatus::Active, $invited->status);
        $this->assertSame($this->elim->id, $invited->church_id);
        $this->assertSame($admin->id, $invited->approved_by);
        $this->assertTrue($invited->hasRole(Role::Servant->value));

        Notification::assertSentTo($invited, StaffInvitation::class, function (StaffInvitation $notification) use ($invited) {
            return str_contains($notification->toMail($invited)->actionUrl, '/set-password/'.$invited->id);
        });
        $this->assertTrue(AuditLog::query()->where('action', 'members.invited')->exists());
    }

    public function test_invitation_validation_and_branch_rules(): void
    {
        $admin = $this->user(Role::Admin);
        User::factory()->create(['email' => 'ada@example.com']);

        $this->actingAs($admin)
            ->post(route('members.invite'), ['name' => 'X', 'email' => 'ada@example.com', 'role' => 'admin'])
            ->assertSessionHasErrors(['email', 'role']);

        $this->actingAs($admin)
            ->post(route('members.invite'), ['name' => 'X', 'email' => 'x@example.com', 'role' => 'servant', 'church_id' => $this->other->id])
            ->assertSessionHasErrors('church_id');
    }

    public function test_superadmin_picks_the_branch_and_may_invite_admins(): void
    {
        $superadmin = $this->user(Role::Superadmin);

        $this->actingAs($superadmin)
            ->get(route('members.index'))
            ->assertInertia(fn (Assert $page) => $page->has('invite.roles', 5)->has('invite.churches', 2));

        $this->actingAs($superadmin)
            ->post(route('members.invite'), ['name' => 'Admin Lain', 'email' => 'admin.lain@example.com', 'role' => 'admin', 'church_id' => $this->other->id])
            ->assertSessionHasNoErrors();

        $invited = Member::query()->where('email', 'admin.lain@example.com')->firstOrFail();
        $this->assertSame($this->other->id, $invited->church_id);
        $this->assertTrue($invited->hasRole(Role::Admin->value));
    }

    private function user(Role $role, ?Church $church = null): User
    {
        $user = User::factory()->create(['church_id' => ($church ?? $this->elim)->id]);
        $user->assignRole($role->value);

        return $user;
    }
}
