<?php

namespace Tests\Modules\Landing;

use App\Models\User;
use App\Modules\Core\Actions\SyncRolesAndPermissions;
use App\Modules\Core\Enums\Role;
use App\Modules\Landing\Database\Seeders\LandingSeeder;
use App\Modules\Landing\Models\SiteSection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PastorAndVideoModeTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->seed(LandingSeeder::class);
        app(SyncRolesAndPermissions::class)->handle();
        $this->admin = User::factory()->create();
        $this->admin->assignRole(Role::Admin->value);
    }

    public function test_videos_show_in_a_frame_by_default(): void
    {
        $this->get('/')->assertInertia(fn (Assert $page) => $page
            ->where('sections.0.key', 'hero')
            ->where('sections.0.content.video_mode', 'frame'));
    }

    public function test_admin_switches_videos_to_background(): void
    {
        $this->actingAs($this->admin)
            ->put('/admin/landing/video-mode', ['video_mode' => 'background'])
            ->assertSessionHasNoErrors();

        $this->actingAs($this->admin)
            ->get('/admin/landing/video')
            ->assertInertia(fn (Assert $page) => $page->where('videoMode', 'background'));

        $this->get('/')->assertInertia(fn (Assert $page) => $page
            ->where('sections.0.content.video_mode', 'background'));
    }

    public function test_unknown_video_mode_is_refused(): void
    {
        $this->actingAs($this->admin)
            ->put('/admin/landing/video-mode', ['video_mode' => 'fullscreen'])
            ->assertSessionHasErrors('video_mode');
    }

    public function test_pastor_section_waits_hidden_until_admin_shows_it(): void
    {
        $this->assertFalse(SiteSection::query()->where('key', 'pastor')->sole()->is_active);

        $this->get('/')->assertInertia(function (Assert $page) {
            $this->assertNotContains('pastor', array_column($page->toArray()['props']['sections'], 'key'));
        });
    }

    public function test_admin_fills_in_the_pastor_section(): void
    {
        $this->actingAs($this->admin)
            ->put('/admin/landing/sections/pastor', [
                'label' => 'Gembala Sidang',
                'name' => 'Pdt. Contoh Nama',
                'paragraphs' => ['Paragraf satu.', 'Paragraf dua.'],
                'more_label' => 'Lihat selengkapnya',
                'more_href' => '/tentang',
                'social_name' => 'Contoh Nama',
                'instagram' => 'https://instagram.com/contoh',
                'youtube' => '',
                'facebook' => '',
            ])
            ->assertSessionHasNoErrors();

        SiteSection::query()->where('key', 'pastor')->update(['is_active' => true]);

        foreach (['/', '/tentang'] as $path) {
            $this->get($path)->assertInertia(function (Assert $page) {
                $pastor = collect($page->toArray()['props']['sections'])->firstWhere('key', 'pastor');

                $this->assertSame('Pdt. Contoh Nama', $pastor['content']['name']);
                $this->assertSame(['Paragraf satu.', 'Paragraf dua.'], $pastor['content']['paragraphs']);
                $this->assertSame('https://instagram.com/contoh', $pastor['content']['instagram']);
                $this->assertArrayNotHasKey('photo', $pastor['content']);
            });
        }
    }

    public function test_social_links_must_be_https(): void
    {
        $this->actingAs($this->admin)
            ->put('/admin/landing/sections/pastor', [
                'label' => 'Gembala Sidang',
                'name' => 'Pdt. Contoh',
                'paragraphs' => ['Bio.'],
                'instagram' => 'javascript:alert(1)',
            ])
            ->assertSessionHasErrors('instagram');
    }

    public function test_admin_uploads_and_removes_the_photo(): void
    {
        if (! extension_loaded('gd')) {
            $this->markTestSkipped('Needs the GD extension to make test images.');
        }

        $this->actingAs($this->admin)
            ->post('/admin/landing/pastor/photo', ['photo' => UploadedFile::fake()->image('gembala.png', 800, 1000)])
            ->assertSessionHasNoErrors();

        $path = SiteSection::query()->where('key', 'pastor')->sole()->content['photo'];
        Storage::disk('public')->assertExists($path);

        $this->actingAs($this->admin)
            ->get('/admin/landing/sections/pastor')
            ->assertInertia(fn (Assert $page) => $page->whereNot('section.photoUrl', null));

        $this->actingAs($this->admin)->delete('/admin/landing/pastor/photo');

        Storage::disk('public')->assertMissing($path);
        $this->assertNull(SiteSection::query()->where('key', 'pastor')->sole()->content['photo']);
    }

    public function test_small_photos_are_refused(): void
    {
        if (! extension_loaded('gd')) {
            $this->markTestSkipped('Needs the GD extension to make test images.');
        }

        $this->actingAs($this->admin)
            ->post('/admin/landing/pastor/photo', ['photo' => UploadedFile::fake()->image('kecil.png', 200, 200)])
            ->assertSessionHasErrors('photo');
    }

    public function test_members_cannot_edit_the_pastor_section(): void
    {
        $member = User::factory()->create();
        $member->assignRole(Role::Member->value);

        $this->actingAs($member)
            ->post('/admin/landing/pastor/photo', ['photo' => UploadedFile::fake()->create('g.png', 100, 'image/png')])
            ->assertForbidden();
    }
}
