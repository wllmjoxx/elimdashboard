<?php

namespace Tests\Modules\Landing;

use App\Models\User;
use App\Modules\Core\Actions\SyncRolesAndPermissions;
use App\Modules\Core\Enums\Role;
use App\Modules\Landing\Database\Seeders\LandingSeeder;
use App\Modules\Landing\Models\ServiceSchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ScheduleAdminTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        app(SyncRolesAndPermissions::class)->handle();
        $this->seed(LandingSeeder::class);
        $this->admin = User::factory()->create();
        $this->admin->assignRole(Role::Admin->value);
    }

    public function test_schedule_page_lists_every_schedule_with_options(): void
    {
        $this->actingAs($this->admin)
            ->get(route('landing.schedule.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('landing/schedule')
                ->has('schedules', 8)
                ->where('schedules.0.category', 'raya')
                ->where('schedules.0.startTime', '08:00')
                ->has('categories', 3)
                ->has('days', 7));
    }

    public function test_admin_adds_updates_hides_and_deletes_schedules(): void
    {
        $this->actingAs($this->admin)
            ->post(route('landing.schedule.store'), [
                'category' => 'komisi',
                'name' => 'Ibadah Pemuda',
                'day' => 6,
                'start_time' => '18:30',
                'location' => 'Aula',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $schedule = ServiceSchedule::query()->where('name', 'Ibadah Pemuda')->firstOrFail();
        $this->assertTrue($schedule->is_active);
        $this->assertSame('Sabtu', $schedule->dayName());

        $this->actingAs($this->admin)
            ->put(route('landing.schedule.update', $schedule), [
                'category' => 'komisi',
                'name' => 'Ibadah Pemuda',
                'day' => 6,
                'start_time' => '19:00',
                'location' => 'Aula',
                'is_active' => false,
            ])
            ->assertSessionHasNoErrors();

        $schedule->refresh();
        $this->assertSame('19.00', $schedule->time());
        $this->assertFalse($schedule->is_active);

        $this->get('/jadwal')->assertInertia(function (Assert $page) {
            $names = collect($page->toArray()['props']['scheduleGroups'])->pluck('items')->flatten(1)->pluck('name');

            $this->assertNotContains('Ibadah Pemuda', $names);
        });

        $this->actingAs($this->admin)->delete(route('landing.schedule.destroy', $schedule))->assertRedirect();
        $this->assertModelMissing($schedule);
    }

    public function test_schedule_validation(): void
    {
        $this->actingAs($this->admin)
            ->post(route('landing.schedule.store'), ['category' => 'lain', 'name' => '', 'day' => 9, 'start_time' => '25:00'])
            ->assertSessionHasErrors(['category', 'name', 'day', 'start_time']);
    }

    public function test_only_landing_managers_manage_the_schedule(): void
    {
        $servant = User::factory()->create();
        $servant->assignRole(Role::Servant->value);

        $this->actingAs($servant)->get(route('landing.schedule.index'))->assertForbidden();
    }
}
