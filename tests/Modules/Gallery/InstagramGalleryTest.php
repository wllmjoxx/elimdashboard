<?php

namespace Tests\Modules\Gallery;

use App\Models\User;
use App\Modules\Core\Actions\SyncRolesAndPermissions;
use App\Modules\Core\Enums\Role;
use App\Modules\Core\Models\Church;
use App\Modules\Gallery\Models\GallerySetting;
use App\Modules\Gallery\Support\InstagramFeed;
use App\Modules\Landing\Database\Seeders\LandingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class InstagramGalleryTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        app(SyncRolesAndPermissions::class)->handle();
        $church = Church::query()->create(['name' => 'GGP Elim', 'slug' => 'ggp-elim']);
        $this->admin = User::factory()->create(['church_id' => $church->id]);
        $this->admin->assignRole(Role::Admin->value);

        Http::fake([
            // Any request with this token is refused, like a revoked token.
            '*bad-token*' => Http::response(['error' => ['message' => 'Invalid OAuth access token']], 400),
            'graph.instagram.com/me/media*' => Http::response(['data' => [
                ['id' => '1', 'caption' => 'Ibadah Raya', 'media_type' => 'IMAGE', 'media_url' => 'https://cdn.example/1.jpg', 'permalink' => 'https://www.instagram.com/p/1/', 'timestamp' => '2026-11-10T08:00:00+0000'],
                ['id' => '2', 'caption' => null, 'media_type' => 'VIDEO', 'media_url' => 'https://cdn.example/2.mp4', 'thumbnail_url' => 'https://cdn.example/2.jpg', 'permalink' => 'https://www.instagram.com/p/2/'],
                ['id' => '3', 'media_type' => 'IMAGE', 'permalink' => 'https://www.instagram.com/p/3/'],
            ]]),
            'graph.instagram.com/me*' => Http::response(['user_id' => '17841400000', 'username' => 'ggpelim']),
            'graph.instagram.com/refresh_access_token*' => Http::response(['access_token' => 'fresh-token', 'expires_in' => 5184000]),
        ]);
    }

    public function test_admin_connects_instagram_with_a_token(): void
    {
        $this->actingAs($this->admin)
            ->put('/admin/gallery/sumber', ['source' => 'instagram', 'token' => 'valid-token'])
            ->assertSessionHasNoErrors();

        $setting = GallerySetting::current();

        $this->assertSame('instagram', $setting->source);
        $this->assertSame('ggpelim', $setting->instagram_username);
        $this->assertSame('valid-token', $setting->instagram_token);
        // Stored encrypted, never in plain text.
        $this->assertStringNotContainsString('valid-token', (string) GallerySetting::query()->toBase()->value('instagram_token'));
    }

    public function test_a_rejected_token_is_reported(): void
    {
        $this->actingAs($this->admin)
            ->put('/admin/gallery/sumber', ['source' => 'instagram', 'token' => 'bad-token'])
            ->assertSessionHasErrors('token');

        $this->assertSame('manual', GallerySetting::current()->source);
    }

    public function test_instagram_mode_needs_a_token(): void
    {
        $this->actingAs($this->admin)
            ->put('/admin/gallery/sumber', ['source' => 'instagram'])
            ->assertSessionHasErrors('token');
    }

    public function test_servants_cannot_change_the_source(): void
    {
        $servant = User::factory()->create();
        $servant->assignRole(Role::Servant->value);

        $this->actingAs($servant)
            ->put('/admin/gallery/sumber', ['source' => 'instagram', 'token' => 'valid-token'])
            ->assertForbidden();
    }

    public function test_admin_page_never_sends_the_token(): void
    {
        $this->connect();

        $this->actingAs($this->admin)
            ->get('/admin/gallery')
            ->assertInertia(fn (Assert $page) => $page
                ->where('source.mode', 'instagram')
                ->where('source.connected', true)
                ->where('source.username', 'ggpelim')
                ->missing('source.token'))
            ->assertDontSee('valid-token');
    }

    public function test_public_gallery_shows_instagram_posts(): void
    {
        $this->connect();

        $this->get('/galeri')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('gallery/public/index')
                ->where('albums', null)
                ->where('instagram.username', 'ggpelim')
                ->has('instagram.posts', 2)
                ->where('instagram.posts.0.image', 'https://cdn.example/1.jpg')
                ->where('instagram.posts.1.image', 'https://cdn.example/2.jpg'));
    }

    public function test_homepage_gallery_uses_instagram_posts(): void
    {
        $this->seed(LandingSeeder::class);
        $this->connect();

        $this->get('/')->assertInertia(function (Assert $page) {
            $gallery = collect($page->toArray()['props']['sections'])->firstWhere('key', 'gallery');

            $this->assertSame('@ggpelim', $gallery['content']['album']);
            $this->assertSame('https://cdn.example/1.jpg', $gallery['content']['items'][0]['image']);
        });
    }

    public function test_posts_are_cached(): void
    {
        $this->connect();
        $feed = app(InstagramFeed::class);

        $feed->posts();
        $feed->posts();

        Http::assertSentCount(1);
    }

    public function test_disconnecting_returns_to_albums(): void
    {
        $this->connect();

        $this->actingAs($this->admin)->delete('/admin/gallery/sumber/instagram');

        $setting = GallerySetting::current();
        $this->assertSame('manual', $setting->source);
        $this->assertNull($setting->instagram_token);
    }

    public function test_token_is_renewed_before_it_expires(): void
    {
        $this->connect(expiresInDays: 10);

        $this->artisan('gallery:refresh-instagram')->assertSuccessful();

        $setting = GallerySetting::current();
        $this->assertSame('fresh-token', $setting->instagram_token);
        $this->assertTrue($setting->instagram_expires_at->gt(now()->addDays(50)));
    }

    public function test_token_is_left_alone_when_not_due(): void
    {
        $this->connect(expiresInDays: 50);

        $this->artisan('gallery:refresh-instagram')->assertSuccessful();

        $this->assertSame('valid-token', GallerySetting::current()->instagram_token);
        Http::assertNothingSent();
    }

    private function connect(int $expiresInDays = 60): void
    {
        GallerySetting::current()->update([
            'source' => 'instagram',
            'instagram_token' => 'valid-token',
            'instagram_user_id' => '17841400000',
            'instagram_username' => 'ggpelim',
            'instagram_expires_at' => now()->addDays($expiresInDays),
        ]);
    }
}
