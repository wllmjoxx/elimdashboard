<?php

namespace Tests\Modules\Landing;

use App\Models\User;
use App\Modules\Core\Actions\SyncRolesAndPermissions;
use App\Modules\Core\Enums\Role;
use App\Modules\Landing\Database\Seeders\LandingSeeder;
use App\Modules\Landing\Models\SiteSection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PrivacyPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_privacy_page_shows_the_policy(): void
    {
        $this->seed(LandingSeeder::class);

        $this->get('/kebijakan-privasi')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('landing/public/page')
                ->where('title', 'Kebijakan Privasi')
                ->where('sections.0.key', 'privacy')
                ->has('sections.0.content.points'));
    }

    public function test_privacy_page_is_in_the_sitemap(): void
    {
        $this->seed(LandingSeeder::class);

        $this->get('/sitemap.xml')->assertSee('<loc>'.url('/kebijakan-privasi').'</loc>', false);
    }

    public function test_admin_edits_the_policy(): void
    {
        $this->seed(LandingSeeder::class);
        app(SyncRolesAndPermissions::class)->handle();
        $admin = User::factory()->create();
        $admin->assignRole(Role::Admin->value);

        $this->actingAs($admin)
            ->put('/admin/landing/sections/privacy', [
                'title' => 'Kebijakan Privasi',
                'effective' => '1 Desember 2026',
                'intro' => '',
                'points' => [
                    5 => ['heading' => 'Data', 'body' => "Baris satu\nBaris dua"],
                ],
            ])
            ->assertSessionHasNoErrors();

        $content = SiteSection::query()->where('key', 'privacy')->sole()->content;

        $this->assertSame('1 Desember 2026', $content['effective']);
        $this->assertSame([['heading' => 'Data', 'body' => "Baris satu\nBaris dua"]], $content['points']);
    }

    public function test_policy_needs_at_least_one_point(): void
    {
        $this->seed(LandingSeeder::class);
        app(SyncRolesAndPermissions::class)->handle();
        $admin = User::factory()->create();
        $admin->assignRole(Role::Admin->value);

        $this->actingAs($admin)
            ->put('/admin/landing/sections/privacy', ['title' => 'Kebijakan Privasi', 'points' => []])
            ->assertSessionHasErrors('points');
    }
}
