<?php

namespace Tests\Modules\Announcements;

use App\Models\User;
use App\Modules\Announcements\Models\Announcement;
use App\Modules\Core\Actions\SyncRolesAndPermissions;
use App\Modules\Core\Enums\Role;
use App\Modules\Core\Models\Church;
use App\Support\Modules\ModuleRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AnnouncementsModuleTest extends TestCase
{
    use RefreshDatabase;

    private Church $elim;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        app(SyncRolesAndPermissions::class)->handle();
        $this->elim = Church::query()->create(['name' => 'GGP Elim', 'slug' => 'ggp-elim']);
        $this->admin = User::factory()->create(['church_id' => $this->elim->id]);
        $this->admin->assignRole(Role::Admin->value);

        $this->travelTo(Carbon::parse('2026-12-02 10:00', 'Asia/Jakarta'));
    }

    public function test_module_is_registered(): void
    {
        $this->assertNotNull(app(ModuleRegistry::class)->find('announcements'));
    }

    public function test_only_admins_manage_announcements(): void
    {
        $member = User::factory()->create(['church_id' => $this->elim->id]);
        $member->assignRole(Role::Member->value);

        $this->get(route('announcements.index'))->assertRedirect(route('login'));
        $this->actingAs($member)->get(route('announcements.index'))->assertForbidden();
        $this->actingAs($member)->post(route('announcements.store'), $this->payload())->assertForbidden();
    }

    public function test_admin_creates_updates_and_deletes_an_announcement(): void
    {
        $this->actingAs($this->admin)
            ->post(route('announcements.store'), $this->payload())
            ->assertRedirect(route('announcements.index'))
            ->assertSessionHasNoErrors();

        $announcement = Announcement::query()->sole();
        $this->assertSame('jadwal-baru', $announcement->slug);
        $this->assertSame($this->elim->id, $announcement->church_id);

        $this->actingAs($this->admin)
            ->put(route('announcements.update', $announcement), [...$this->payload(), 'is_pinned' => true])
            ->assertSessionHasNoErrors();
        $this->assertTrue($announcement->refresh()->is_pinned);

        $this->actingAs($this->admin)->delete(route('announcements.destroy', $announcement));
        $this->assertModelMissing($announcement);
    }

    public function test_expiry_cannot_come_before_publishing(): void
    {
        $this->actingAs($this->admin)
            ->post(route('announcements.store'), [...$this->payload(), 'expires_on' => '2026-11-30'])
            ->assertSessionHasErrors('expires_on');
    }

    public function test_public_page_shows_only_current_ones_pinned_first(): void
    {
        $this->announcement('Biasa Baru', '2026-12-01');
        $this->announcement('Disematkan Lama', '2026-11-20', pinned: true);
        $this->announcement('Draf', '2026-12-01', published: false);
        $this->announcement('Terjadwal', '2026-12-05');
        $this->announcement('Sudah Berakhir', '2026-11-01', expires: '2026-12-01');
        $this->announcement('Berakhir Hari Ini', '2026-11-25', expires: '2026-12-02');

        $this->get('/pengumuman')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('announcements/public/index')
                ->has('announcements.data', 3)
                ->where('announcements.data.0.title', 'Disematkan Lama')
                ->where('announcements.data.1.title', 'Biasa Baru')
                ->where('announcements.data.2.title', 'Berakhir Hari Ini'));
    }

    public function test_expired_announcements_have_no_page(): void
    {
        $old = $this->announcement('Lama', '2026-11-01', expires: '2026-11-10');
        $current = $this->announcement('Baru', '2026-12-01');

        $this->get("/pengumuman/{$old->slug}")->assertNotFound();
        $this->get("/pengumuman/{$current->slug}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('announcement.title', 'Baru'));
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(): array
    {
        return [
            'title' => 'Jadwal baru',
            'body' => "Paragraf satu.\n\nParagraf dua.",
            'is_pinned' => false,
            'is_published' => true,
            'published_on' => '2026-12-02',
            'expires_on' => '2026-12-31',
        ];
    }

    private function announcement(string $title, string $publishedOn, ?string $expires = null, bool $pinned = false, bool $published = true): Announcement
    {
        $announcement = new Announcement([
            'title' => $title,
            'body' => 'Isi.',
            'published_on' => $publishedOn,
            'expires_on' => $expires,
            'is_pinned' => $pinned,
            'is_published' => $published,
        ]);
        $announcement->church_id = $this->elim->id;
        $announcement->save();

        return $announcement;
    }
}
