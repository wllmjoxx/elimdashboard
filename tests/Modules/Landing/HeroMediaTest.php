<?php

namespace Tests\Modules\Landing;

use App\Models\User;
use App\Modules\Core\Actions\SyncRolesAndPermissions;
use App\Modules\Core\Enums\Role;
use App\Modules\Landing\Database\Seeders\LandingSeeder;
use App\Modules\Landing\Models\HeroVideo;
use App\Modules\Landing\Models\SiteSection;
use App\Modules\Landing\Support\LiveStream;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class HeroMediaTest extends TestCase
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

    public function test_admin_sees_the_video_and_live_page(): void
    {
        $this->actingAs($this->admin)
            ->get('/admin/landing/video')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('landing/media')
                ->has('videos', 0)
                ->where('live.isLive', false)
                ->where('rules.maxCount', 5));
    }

    public function test_members_cannot_manage_videos(): void
    {
        $member = User::factory()->create();
        $member->assignRole(Role::Member->value);

        $this->actingAs($member)->get('/admin/landing/video')->assertForbidden();
        $this->actingAs($member)
            ->post('/admin/landing/videos', ['video' => $this->video()])
            ->assertForbidden();
    }

    public function test_admin_uploads_a_video_with_a_poster(): void
    {
        $this->actingAs($this->admin)
            ->post('/admin/landing/videos', [
                'video' => $this->video(),
                'title' => 'Ibadah Raya',
                'poster' => UploadedFile::fake()->create('sampul.jpg', 200, 'image/jpeg'),
            ])
            ->assertSessionHasNoErrors();

        $video = HeroVideo::query()->sole();

        $this->assertSame('Ibadah Raya', $video->title);
        Storage::disk('public')->assertExists($video->path);
        Storage::disk('public')->assertExists((string) $video->poster_path);
    }

    public function test_only_video_files_are_accepted(): void
    {
        $this->actingAs($this->admin)
            ->post('/admin/landing/videos', ['video' => UploadedFile::fake()->create('lagu.mp3', 500, 'audio/mpeg')])
            ->assertSessionHasErrors('video');

        $this->assertDatabaseCount('hero_videos', 0);
    }

    public function test_video_count_is_limited(): void
    {
        config(['modules.landing.hero_video.max_count' => 1]);
        $this->actingAs($this->admin)->post('/admin/landing/videos', ['video' => $this->video()]);

        $this->actingAs($this->admin)
            ->post('/admin/landing/videos', ['video' => $this->video()])
            ->assertSessionHasErrors('video');

        $this->assertDatabaseCount('hero_videos', 1);
    }

    public function test_admin_hides_reorders_and_deletes_videos(): void
    {
        $first = $this->storedVideo('Satu');
        $second = $this->storedVideo('Dua');

        $this->actingAs($this->admin)
            ->patch("/admin/landing/videos/{$first->id}", ['title' => 'Satu', 'is_active' => false])
            ->assertSessionHasNoErrors();
        $this->assertFalse($first->refresh()->is_active);

        $this->actingAs($this->admin)
            ->post('/admin/landing/videos/order', ['ids' => [$second->id, $first->id]])
            ->assertSessionHasNoErrors();
        $this->assertSame([$second->id, $first->id], HeroVideo::query()->ordered()->pluck('id')->all());

        $this->actingAs($this->admin)->delete("/admin/landing/videos/{$second->id}");
        $this->assertModelMissing($second);
        Storage::disk('public')->assertMissing($second->path);
    }

    public function test_homepage_plays_only_active_videos(): void
    {
        $this->storedVideo('Tampil');
        $this->storedVideo('Tersembunyi')->update(['is_active' => false]);

        $this->get('/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('sections.0.key', 'hero')
                ->has('sections.0.content.videos', 1)
                ->where('sections.0.content.videos.0.title', 'Tampil')
                ->where('sections.0.content.live.is_live', false));
    }

    public function test_live_stream_replaces_the_videos_while_on_air(): void
    {
        $this->actingAs($this->admin)
            ->put('/admin/landing/live', [
                'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                'title' => 'Ibadah Raya I',
                'is_live' => true,
            ])
            ->assertSessionHasNoErrors();

        $this->get('/')
            ->assertInertia(fn (Assert $page) => $page
                ->where('sections.0.content.live.is_live', true)
                ->where('sections.0.content.live.title', 'Ibadah Raya I')
                ->where('sections.0.content.live.embed_url', 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ?autoplay=1&mute=1&playsinline=1&rel=0'));
    }

    public function test_marking_live_needs_a_link(): void
    {
        $this->actingAs($this->admin)
            ->put('/admin/landing/live', ['url' => '', 'is_live' => true])
            ->assertSessionHasErrors('url');
    }

    public function test_live_turns_itself_off_after_the_cut_off(): void
    {
        $this->actingAs($this->admin)->put('/admin/landing/live', [
            'url' => 'https://youtu.be/dQw4w9WgXcQ',
            'is_live' => true,
        ]);

        // Visitors see the homepage; the admin's own session would time out.
        $this->app['auth']->guard('web')->logout();
        $this->travel(7)->hours();

        $this->get('/')->assertInertia(fn (Assert $page) => $page
            ->where('sections.0.content.live.is_live', false)
            ->where('sections.0.content.live.url', null));
    }

    public function test_turning_live_off_keeps_the_link_for_next_time(): void
    {
        $this->actingAs($this->admin)->put('/admin/landing/live', ['url' => 'https://youtu.be/dQw4w9WgXcQ', 'is_live' => true]);
        $this->actingAs($this->admin)->put('/admin/landing/live', ['url' => 'https://youtu.be/dQw4w9WgXcQ', 'is_live' => false]);

        $live = SiteSection::query()->where('key', 'hero')->sole()->content['live'];

        $this->assertFalse($live['is_live']);
        $this->assertSame('https://youtu.be/dQw4w9WgXcQ', $live['url']);
        $this->assertNull($live['started_at']);
    }

    /**
     * @return array<string, array{0: string, 1: string|null}>
     */
    public static function links(): array
    {
        return [
            'watch' => ['https://www.youtube.com/watch?v=dQw4w9WgXcQ&t=10', 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ?autoplay=1&mute=1&playsinline=1&rel=0'],
            'short link' => ['https://youtu.be/dQw4w9WgXcQ', 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ?autoplay=1&mute=1&playsinline=1&rel=0'],
            'live path' => ['https://youtube.com/live/dQw4w9WgXcQ?feature=share', 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ?autoplay=1&mute=1&playsinline=1&rel=0'],
            'channel' => ['https://www.youtube.com/channel/UC1234567890abcdefghijkl/live', 'https://www.youtube.com/embed/live_stream?channel=UC1234567890abcdefghijkl&autoplay=1&mute=1&playsinline=1'],
            'handle' => ['https://www.youtube.com/@ggpelim/live', null],
            'other site' => ['https://www.facebook.com/ggpelim/live', null],
        ];
    }

    #[DataProvider('links')]
    public function test_youtube_links_become_embeds(string $url, ?string $embed): void
    {
        $this->assertSame($embed, LiveStream::embedUrl($url));
    }

    private function video(): UploadedFile
    {
        return UploadedFile::fake()->create('ibadah.mp4', 2048, 'video/mp4');
    }

    private function storedVideo(string $title): HeroVideo
    {
        return HeroVideo::query()->create([
            'title' => $title,
            'path' => UploadedFile::fake()->create('v.mp4', 10, 'video/mp4')->store('landing/videos', 'public'),
            'mime' => 'video/mp4',
            'size' => 10240,
            'sort_order' => (int) HeroVideo::query()->max('sort_order') + 10,
        ]);
    }
}
