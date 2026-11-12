<?php

namespace Tests\Modules\Events;

use App\Models\User;
use App\Modules\Core\Actions\SyncRolesAndPermissions;
use App\Modules\Core\Enums\Role;
use App\Modules\Core\Models\Church;
use App\Modules\Events\Models\Event;
use App\Modules\Landing\Database\Seeders\LandingSeeder;
use App\Support\Modules\ModuleRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class EventsModuleTest extends TestCase
{
    use RefreshDatabase;

    private Church $elim;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        app(SyncRolesAndPermissions::class)->handle();
        $this->elim = Church::query()->create(['name' => 'GGP Elim', 'slug' => 'ggp-elim']);
        $this->admin = $this->userWithRole(Role::Admin);

        // Wednesday 1 December 2026, 10:00 in church time.
        $this->travelTo(Carbon::parse('2026-12-02 10:00', 'Asia/Jakarta'));
    }

    public function test_module_is_registered(): void
    {
        $this->assertNotNull(app(ModuleRegistry::class)->find('events'));
    }

    public function test_only_admins_manage_events(): void
    {
        $this->get(route('events.index'))->assertRedirect(route('login'));
        $this->actingAs($this->userWithRole(Role::Member))->get(route('events.index'))->assertForbidden();
        $this->actingAs($this->userWithRole(Role::Servant))->post(route('events.store'), $this->payload())->assertForbidden();

        $this->actingAs($this->admin)
            ->get(route('events.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('events/index'));
    }

    public function test_admin_creates_updates_and_deletes_an_event(): void
    {
        $this->actingAs($this->admin)
            ->post(route('events.store'), [
                ...$this->payload(),
                'image' => UploadedFile::fake()->create('poster.jpg', 200, 'image/jpeg'),
            ])
            ->assertRedirect(route('events.index'))
            ->assertSessionHasNoErrors();

        $event = Event::query()->sole();
        $this->assertSame('retret-youth', $event->slug);
        $this->assertSame($this->elim->id, $event->church_id);
        Storage::disk('public')->assertExists((string) $event->image_path);
        $image = $event->image_path;

        $this->actingAs($this->admin)
            ->put(route('events.update', $event), [...$this->payload(), 'title' => 'Retret Youth Elim', 'remove_image' => true])
            ->assertSessionHasNoErrors();

        $this->assertSame('Retret Youth Elim', $event->refresh()->title);
        $this->assertNull($event->image_path);
        Storage::disk('public')->assertMissing((string) $image);

        $this->actingAs($this->admin)->delete(route('events.destroy', $event))->assertRedirect(route('events.index'));
        $this->assertModelMissing($event);
    }

    public function test_end_must_come_after_start(): void
    {
        $this->actingAs($this->admin)
            ->post(route('events.store'), [...$this->payload(), 'ends_at' => '2026-12-10T07:00'])
            ->assertSessionHasErrors('ends_at');
    }

    public function test_public_page_groups_events_by_time(): void
    {
        $this->event('Sedang Jalan', '2026-12-01 08:00', '2026-12-04 17:00');
        $this->event('Hari Ini Tanpa Selesai', '2026-12-02 06:00', null);
        $this->event('Minggu Depan', '2026-12-09 18:00', '2026-12-09 21:00');
        $this->event('Bulan Lalu', '2026-11-01 09:00', '2026-11-01 12:00');
        $this->event('Disembunyikan', '2026-12-05 09:00', null, published: false);

        $this->get('/kegiatan')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('events/public/index')
                ->has('ongoing', 2)
                ->where('ongoing.0.title', 'Sedang Jalan')
                ->where('ongoing.0.state', 'ongoing')
                ->has('upcoming', 1)
                ->where('upcoming.0.title', 'Minggu Depan')
                ->where('upcoming.0.timeLabel', '18.00–21.00 WIB')
                ->has('past', 1)
                ->where('past.0.title', 'Bulan Lalu'));
    }

    public function test_hidden_events_have_no_page(): void
    {
        $hidden = $this->event('Rahasia', '2026-12-05 09:00', null, published: false);
        $shown = $this->event('Terbuka', '2026-12-05 09:00', null);

        $this->get("/kegiatan/{$hidden->slug}")->assertNotFound();
        $this->get("/kegiatan/{$shown->slug}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('events/public/show')->where('event.title', 'Terbuka'));
    }

    public function test_homepage_shows_ongoing_then_upcoming_events(): void
    {
        $this->seed(LandingSeeder::class);
        $this->event('Akan Datang', '2026-12-09 18:00', null);
        $this->event('Sedang Jalan', '2026-12-01 08:00', '2026-12-04 17:00');
        $this->event('Sudah Lewat', '2026-11-01 09:00', null);

        $this->get('/')->assertInertia(function (Assert $page) {
            $events = collect($page->toArray()['props']['sections'])->firstWhere('key', 'events');

            $this->assertSame(['Sedang Jalan', 'Akan Datang'], array_column($events['content']['items'], 'title'));
            $this->assertSame(route('events.public.index'), $events['content']['url']);
        });
    }

    public function test_events_are_in_the_sitemap(): void
    {
        $event = $this->event('Kebaktian Kebangunan Rohani', '2026-12-09 18:00', null);

        $this->get('/sitemap.xml')
            ->assertSee('<loc>'.url('/kegiatan').'</loc>', false)
            ->assertSee('<loc>'.url("/kegiatan/{$event->slug}").'</loc>', false);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(): array
    {
        return [
            'title' => 'Retret Youth',
            'summary' => 'Tiga hari bersama.',
            'location' => 'Puncak',
            'starts_at' => '2026-12-12T07:00',
            'ends_at' => '2026-12-14T15:00',
            'registration_url' => 'https://forms.gle/contoh',
            'is_published' => true,
        ];
    }

    private function event(string $title, string $start, ?string $end, bool $published = true): Event
    {
        $event = new Event(['title' => $title, 'starts_at' => $start, 'ends_at' => $end]);
        $event->church_id = $this->elim->id;
        $event->is_published = $published;
        $event->save();

        return $event;
    }

    private function userWithRole(Role $role): User
    {
        $user = User::factory()->create(['church_id' => $this->elim->id]);
        $user->assignRole($role->value);

        return $user;
    }
}
