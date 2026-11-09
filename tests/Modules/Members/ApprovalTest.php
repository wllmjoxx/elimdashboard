<?php

namespace Tests\Modules\Members;

use App\Models\User;
use App\Modules\Core\Actions\SyncRolesAndPermissions;
use App\Modules\Core\Enums\Role;
use App\Modules\Core\Models\Church;
use App\Modules\Members\Enums\MemberStatus;
use App\Modules\Members\Http\Controllers\SetPasswordController;
use App\Modules\Members\Models\Member;
use App\Modules\Members\Notifications\RegistrationApproved;
use App\Modules\Members\Notifications\RegistrationNeedsInfo;
use App\Modules\Members\Notifications\RegistrationRejected;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ApprovalTest extends TestCase
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

    public function test_admin_sees_only_pending_registrations_of_their_branch(): void
    {
        $this->pending($this->elim, 'elim@example.com');
        $this->pending($this->other, 'lain@example.com');

        $this->actingAs($this->staff(Role::Admin, $this->elim))
            ->get(route('members.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('status', 'pending')
                ->has('members.data', 1)
                ->where('members.data.0.email', 'elim@example.com')
                ->where('members.data.0.can.review', true)
                ->where('statuses.0.count', 1)
                ->where('showChurch', false));
    }

    public function test_superadmin_sees_every_branch(): void
    {
        $this->pending($this->elim, 'elim@example.com');
        $this->pending($this->other, 'lain@example.com');

        $this->actingAs($this->staff(Role::Superadmin, null))
            ->get(route('members.index'))
            ->assertInertia(fn (Assert $page) => $page->has('members.data', 2)->where('showChurch', true));
    }

    public function test_approving_activates_the_account_and_emails_a_password_link(): void
    {
        $admin = $this->staff(Role::Admin, $this->elim);
        $member = $this->pending($this->elim);

        $this->actingAs($admin)->post(route('members.approve', $member))->assertRedirect();

        $member->refresh();
        $this->assertSame(MemberStatus::Active, $member->status);
        $this->assertSame($admin->id, $member->approved_by);
        $this->assertNotNull($member->approved_at);
        $this->assertTrue($member->hasRole(Role::Member->value));

        Notification::assertSentTo($member, RegistrationApproved::class, function (RegistrationApproved $notification) use ($member) {
            return str_contains($notification->toMail($member)->actionUrl, '/set-password/'.$member->id);
        });
    }

    public function test_rejecting_needs_a_reason_and_emails_it(): void
    {
        $admin = $this->staff(Role::Admin, $this->elim);
        $member = $this->pending($this->elim);

        $this->actingAs($admin)->post(route('members.reject', $member), ['note' => ''])->assertSessionHasErrors('note');

        $this->actingAs($admin)->post(route('members.reject', $member), ['note' => 'Data tidak cocok.'])->assertRedirect();

        $member->refresh();
        $this->assertSame(MemberStatus::Rejected, $member->status);
        $this->assertSame('Data tidak cocok.', $member->review_note);
        Notification::assertSentTo($member, RegistrationRejected::class, fn ($notification) => $notification->reason === 'Data tidak cocok.');
    }

    public function test_requesting_more_data_keeps_the_registration_pending(): void
    {
        $member = $this->pending($this->elim);

        $this->actingAs($this->staff(Role::Admin, $this->elim))
            ->post(route('members.request-info', $member), ['note' => 'Mohon kirim nomor HP.'])
            ->assertRedirect();

        $member->refresh();
        $this->assertSame(MemberStatus::Pending, $member->status);
        $this->assertSame('Mohon kirim nomor HP.', $member->review_note);
        Notification::assertSentTo($member, RegistrationNeedsInfo::class);
    }

    public function test_admin_cannot_review_another_branch(): void
    {
        $member = $this->pending($this->other);

        $this->actingAs($this->staff(Role::Admin, $this->elim))
            ->post(route('members.approve', $member))
            ->assertForbidden();

        $this->assertSame(MemberStatus::Pending, $member->refresh()->status);
    }

    public function test_roles_without_permission_cannot_review(): void
    {
        $member = $this->pending($this->elim);

        $this->actingAs($this->staff(Role::PrayerTeam, $this->elim))
            ->post(route('members.approve', $member))
            ->assertForbidden();
    }

    public function test_only_pending_registrations_can_be_reviewed(): void
    {
        $member = $this->pending($this->elim);
        $member->forceFill(['status' => MemberStatus::Active])->save();

        $this->actingAs($this->staff(Role::Admin, $this->elim))
            ->post(route('members.approve', $member))
            ->assertStatus(409);
    }

    public function test_accounts_can_be_deactivated_and_reactivated_but_not_your_own(): void
    {
        $admin = $this->staff(Role::Admin, $this->elim);
        $member = $this->pending($this->elim);
        $member->forceFill(['status' => MemberStatus::Active])->save();

        $this->actingAs($admin)->post(route('members.status', $member), ['status' => 'inactive'])->assertRedirect();
        $this->assertSame(MemberStatus::Inactive, $member->refresh()->status);

        $this->actingAs($admin)->post(route('members.status', $member), ['status' => 'active'])->assertRedirect();
        $this->assertSame(MemberStatus::Active, $member->refresh()->status);

        $this->actingAs($admin)->post(route('members.status', $admin->id), ['status' => 'inactive'])->assertForbidden();
    }

    public function test_password_link_sets_the_password_signs_in_and_then_expires(): void
    {
        $member = $this->pending($this->elim);
        $this->actingAs($this->staff(Role::Admin, $this->elim))->post(route('members.approve', $member));
        auth()->logout();

        $link = SetPasswordController::link($member->refresh());

        $this->get($link)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('members/auth/set-password')->where('email', $member->email));

        $this->post($link, ['password' => 'rahasia-baru-123', 'password_confirmation' => 'rahasia-baru-123'])
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($member);
        $this->assertNotNull($member->refresh()->email_verified_at);

        auth()->logout();
        $this->get($link)->assertForbidden();
    }

    public function test_password_link_is_refused_when_tampered_or_not_approved(): void
    {
        $member = $this->pending($this->elim);

        // Not approved yet.
        $this->get(SetPasswordController::link($member))->assertForbidden();

        $member->forceFill(['status' => MemberStatus::Active])->save();
        $link = SetPasswordController::link($member);

        $this->get($link.'x')->assertForbidden();
        $this->get(str_replace('hash=', 'hash=0', $link))->assertForbidden();
    }

    private function pending(Church $church, ?string $email = null): Member
    {
        $member = new Member;
        $member->forceFill([
            'name' => 'Pendaftar',
            'email' => $email ?? Str::random(8).'@example.com',
            'church_id' => $church->id,
            'status' => MemberStatus::Pending,
            'password' => Str::random(64),
        ])->save();

        return $member;
    }

    private function staff(Role $role, ?Church $church): User
    {
        $user = User::factory()->create(['church_id' => $church?->id]);
        $user->assignRole($role->value);

        return $user;
    }
}
