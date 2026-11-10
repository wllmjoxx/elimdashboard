<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Core\Actions\SyncRolesAndPermissions;
use App\Modules\Core\Enums\Role;
use App\Modules\Core\Models\Church;
use App\Modules\Members\Enums\MemberStatus;
use App\Modules\Members\Models\Member;
use App\Modules\Prayer\Enums\PrayerStatus;
use App\Modules\Prayer\Models\PrayerRequest;
use App\Modules\Testimonies\Models\Testimony;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private Church $elim;

    protected function setUp(): void
    {
        parent::setUp();

        app(SyncRolesAndPermissions::class)->handle();
        $this->elim = Church::query()->create(['name' => 'GGP Elim', 'slug' => 'ggp-elim']);
    }

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_the_dashboard(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('dashboard')->has('cards'));
    }

    public function test_admin_sees_counts_for_their_branch_with_highlights(): void
    {
        $other = Church::query()->create(['name' => 'GGP Lain', 'slug' => 'ggp-lain']);
        $this->member(MemberStatus::Pending, $this->elim);
        $this->member(MemberStatus::Pending, $this->elim);
        $this->member(MemberStatus::Pending, $other);
        $this->prayer($this->elim, PrayerStatus::New);
        $this->prayer($this->elim, PrayerStatus::Prayed);
        $this->prayer($other, PrayerStatus::New);
        $this->testimony($this->elim);

        $cards = $this->cardsFor($this->user(Role::Admin));

        $this->assertSame(2, $cards['members.pending']['value']);
        $this->assertTrue($cards['members.pending']['highlight']);
        $this->assertSame('/admin/members?status=pending', $cards['members.pending']['href']);
        $this->assertSame(2, $cards['prayer.week']['value']);
        $this->assertSame(1, $cards['prayer.new']['value']);
        $this->assertSame(1, $cards['testimonies.pending']['value']);
        $this->assertFalse($cards['devotionals.drafts']['highlight']);
        $this->assertSame(0, $cards['devotionals.drafts']['value']);
    }

    public function test_cards_follow_permissions(): void
    {
        $member = $this->cardsFor($this->user(Role::Member));
        $this->assertSame(['prayer.mine'], array_keys($member));

        $prayerTeam = $this->cardsFor($this->user(Role::PrayerTeam));
        $this->assertEqualsCanonicalizing(['prayer.week', 'prayer.new', 'prayer.mine'], array_keys($prayerTeam));

        $servant = $this->cardsFor($this->user(Role::Servant));
        $this->assertArrayHasKey('devotionals.scheduled', $servant);
        $this->assertArrayNotHasKey('devotionals.drafts', $servant);
        $this->assertArrayNotHasKey('members.pending', $servant);
    }

    public function test_cards_are_sorted_by_order(): void
    {
        $keys = array_keys($this->cardsFor($this->user(Role::Superadmin)));

        $this->assertSame('members.pending', $keys[0]);
        $this->assertSame('prayer.mine', end($keys));
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function cardsFor(User $user): array
    {
        $cards = [];

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertInertia(function (Assert $page) use (&$cards) {
                $cards = collect($page->toArray()['props']['cards'])->keyBy('key')->all();
            });

        return $cards;
    }

    private function user(Role $role): User
    {
        $user = User::factory()->create(['church_id' => $this->elim->id]);
        $user->assignRole($role->value);

        return $user;
    }

    private function member(MemberStatus $status, Church $church): void
    {
        (new Member)->forceFill([
            'name' => 'Pendaftar',
            'email' => Str::random(8).'@example.com',
            'church_id' => $church->id,
            'status' => $status,
            'password' => Str::random(32),
        ])->save();
    }

    private function prayer(Church $church, PrayerStatus $status): void
    {
        $prayer = new PrayerRequest(['full_name' => 'Tamu', 'body' => 'Mohon doa.', 'category' => 'family']);
        $prayer->church_id = $church->id;
        $prayer->status = $status;
        $prayer->week_start = PrayerRequest::currentWeekStart();
        $prayer->save();
    }

    private function testimony(Church $church): void
    {
        $testimony = new Testimony(['title' => 'Kesaksian', 'body' => 'Isi kesaksian.', 'category' => 'other']);
        $testimony->church_id = $church->id;
        $testimony->save();
    }
}
