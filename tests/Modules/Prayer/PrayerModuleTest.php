<?php

namespace Tests\Modules\Prayer;

use App\Models\User;
use App\Modules\Core\Actions\SyncRolesAndPermissions;
use App\Modules\Core\Enums\Role;
use App\Modules\Core\Models\AuditLog;
use App\Modules\Core\Models\Church;
use App\Modules\Prayer\Database\Seeders\PrayerSeeder;
use App\Modules\Prayer\Enums\PrayerStatus;
use App\Modules\Prayer\Models\PrayerRequest;
use App\Support\Modules\ModuleRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PrayerModuleTest extends TestCase
{
    use RefreshDatabase;

    private Church $elim;

    private Church $other;

    protected function setUp(): void
    {
        parent::setUp();

        app(SyncRolesAndPermissions::class)->handle();
        $this->elim = Church::query()->create(['name' => 'GGP Elim', 'slug' => 'ggp-elim']);
        $this->other = Church::query()->create(['name' => 'GGP Lain', 'slug' => 'ggp-lain']);
    }

    public function test_module_is_registered(): void
    {
        $this->assertNotNull(app(ModuleRegistry::class)->find('prayer'));
    }

    public function test_only_prayer_team_admin_and_superadmin_open_the_panel(): void
    {
        $this->get(route('prayer.index'))->assertRedirect(route('login'));

        foreach ([Role::Member, Role::Servant] as $role) {
            $this->actingAs($this->user($role))->get(route('prayer.index'))->assertForbidden();
        }

        foreach ([Role::PrayerTeam, Role::Admin, Role::Superadmin] as $role) {
            $this->actingAs($this->user($role))->get(route('prayer.index'))->assertOk();
        }
    }

    public function test_panel_lists_this_weeks_requests_of_the_branch_and_logs_the_view(): void
    {
        $team = $this->user(Role::PrayerTeam);
        $this->prayer('Minggu ini');
        $this->prayer('Minggu lalu', PrayerRequest::currentWeekStart()->subWeek());
        $this->prayer('Cabang lain', null, $this->other);

        $this->actingAs($team)
            ->get(route('prayer.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('prayer/index')
                ->has('requests', 1)
                ->where('requests.0.fullName', 'Minggu ini')
                ->where('requests.0.can.mark', true)
                ->where('requests.0.can.delete', false)
                ->where('week.isCurrent', true)
                ->where('week.hasNext', false));

        $log = AuditLog::query()->latest('id')->firstOrFail();
        $this->assertSame('prayer.week.viewed', $log->action);
        $this->assertSame($team->id, $log->user_id);
        $this->assertSame(1, $log->metadata['count']);
    }

    public function test_previous_weeks_and_filters(): void
    {
        $lastWeek = PrayerRequest::currentWeekStart()->subWeek();
        $this->prayer('Lama', $lastWeek);
        $this->prayer('Terjawab', $lastWeek, status: PrayerStatus::Answered);

        $this->actingAs($this->user(Role::PrayerTeam))
            ->get(route('prayer.index', ['week' => $lastWeek->toDateString(), 'status' => 'answered']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('requests', 1)
                ->where('requests.0.fullName', 'Terjawab')
                ->where('week.hasNext', true)
                ->where('statuses.0.count', 1)
                ->where('statuses.2.count', 1));
    }

    public function test_prayer_team_marks_prayed_and_answered(): void
    {
        $team = $this->user(Role::PrayerTeam);
        $prayer = $this->prayer('Maria');

        $this->actingAs($team)->post(route('prayer.status', $prayer), ['status' => 'prayed'])->assertRedirect();
        $prayer->refresh();
        $this->assertSame(PrayerStatus::Prayed, $prayer->status);
        $this->assertSame($team->id, $prayer->prayed_by);
        $this->assertNotNull($prayer->prayed_at);

        $this->actingAs($team)->post(route('prayer.status', $prayer), ['status' => 'answered']);
        $this->assertSame(PrayerStatus::Answered, $prayer->refresh()->status);
        $this->assertNotNull($prayer->answered_at);

        $this->assertSame(2, AuditLog::query()->where('action', 'prayer.status.changed')->where('subject_id', $prayer->id)->count());
    }

    public function test_only_admin_deletes_and_it_is_logged(): void
    {
        $prayer = $this->prayer('Maria');

        $this->actingAs($this->user(Role::PrayerTeam))->delete(route('prayer.destroy', $prayer))->assertForbidden();

        $this->actingAs($this->user(Role::Admin))->delete(route('prayer.destroy', $prayer))->assertRedirect();
        $this->assertModelMissing($prayer);
        $this->assertTrue(AuditLog::query()->where('action', 'prayer.deleted')->exists());
    }

    public function test_requests_of_another_branch_cannot_be_touched(): void
    {
        $outside = $this->prayer('Lain', null, $this->other);

        $this->actingAs($this->user(Role::Admin))->post(route('prayer.status', $outside), ['status' => 'prayed'])->assertNotFound();
        $this->actingAs($this->user(Role::Admin))->delete(route('prayer.destroy', $outside))->assertNotFound();
    }

    public function test_print_view_lists_the_week_and_is_logged(): void
    {
        $this->prayer('Maria');

        $this->actingAs($this->user(Role::PrayerTeam))
            ->get(route('prayer.print'))
            ->assertOk()
            ->assertSee('Daftar Pokok Doa')
            ->assertSee('Maria')
            ->assertSee('Rahasia');

        $this->assertTrue(AuditLog::query()->where('action', 'prayer.week.exported')->exists());

        $this->actingAs($this->user(Role::Servant))->get(route('prayer.print'))->assertForbidden();
    }

    public function test_prayer_content_is_hidden_from_serialization(): void
    {
        $prayer = $this->prayer('Maria');

        $this->assertArrayNotHasKey('body', $prayer->toArray());
        $this->assertArrayNotHasKey('full_name', $prayer->toArray());
    }

    public function test_seeder_adds_sample_requests_once(): void
    {
        Church::query()->whereKey($this->other->id)->delete();

        $this->seed(PrayerSeeder::class);
        $this->seed(PrayerSeeder::class);

        $this->assertSame(4, PrayerRequest::query()->withoutGlobalScopes()->count());
    }

    private function prayer(string $name, ?Carbon $week = null, ?Church $church = null, PrayerStatus $status = PrayerStatus::New): PrayerRequest
    {
        $prayer = new PrayerRequest([
            'full_name' => $name,
            'body' => 'Mohon doa.',
            'category' => 'family',
        ]);
        $prayer->church_id = ($church ?? $this->elim)->id;
        $prayer->week_start = $week ?? PrayerRequest::currentWeekStart();
        $prayer->status = $status;
        $prayer->save();

        return $prayer;
    }

    private function user(Role $role): User
    {
        $user = User::factory()->create(['church_id' => $this->elim->id]);
        $user->assignRole($role->value);

        return $user;
    }
}
