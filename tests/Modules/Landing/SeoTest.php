<?php

namespace Tests\Modules\Landing;

use App\Models\User;
use App\Modules\Core\Models\Church;
use App\Modules\Devotionals\Enums\DevotionalStatus;
use App\Modules\Devotionals\Models\Devotional;
use App\Modules\Landing\Database\Seeders\LandingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_has_server_rendered_meta_and_church_schema(): void
    {
        $this->seed(LandingSeeder::class);

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('<meta name="description" content="GGP Elim Ciputat', $html);
        $this->assertStringContainsString('<link rel="canonical" href="'.url('/').'">', $html);
        $this->assertStringContainsString('<meta property="og:title" content="'.config('app.name').'">', $html);
        $this->assertStringContainsString('<meta property="og:locale" content="id_ID">', $html);
        $this->assertStringContainsString('<meta name="geo.region" content="ID-BT">', $html);
        $this->assertStringContainsString('<meta name="geo.placename" content="Ciputat, Tangerang Selatan">', $html);

        $schema = $this->schema($html);
        $this->assertSame('Church', $schema['@type']);
        $this->assertSame('Ciputat', $schema['address']['addressLocality']);
        $this->assertSame('+6285883858048', $schema['telephone']);
    }

    public function test_pages_outside_production_are_not_indexed(): void
    {
        $this->seed(LandingSeeder::class);

        $this->assertStringContainsString(
            '<meta name="robots" content="noindex, nofollow">',
            $this->get('/tentang')->getContent(),
        );
    }

    public function test_page_title_and_description_come_from_the_controller(): void
    {
        $this->seed(LandingSeeder::class);

        $html = $this->get('/tentang')->assertOk()->getContent();

        $this->assertMatchesRegularExpression('#<title[^>]*>Tentang GGP Elim - '.preg_quote((string) config('app.name')).'</title>#', $html);
        $this->assertStringContainsString('<meta property="og:description" content="Sejarah, misi, dan perjalanan GGP Jemaat Elim Ciputat.">', $html);
        $this->assertStringNotContainsString('application/ld+json', $html);
    }

    public function test_devotional_page_is_an_article(): void
    {
        $devotional = $this->devotional('Gembala yang Baik', DevotionalStatus::Published);

        $html = $this->get("/renungan/{$devotional->slug}")->assertOk()->getContent();

        $this->assertStringContainsString('<meta property="og:type" content="article">', $html);
        $this->assertStringContainsString('<meta property="og:title" content="Gembala yang Baik - '.config('app.name').'">', $html);
        $this->assertStringContainsString('<meta property="article:published_time"', $html);
    }

    public function test_admin_pages_are_marked_noindex_without_public_tags(): void
    {
        $html = $this->actingAs(User::factory()->create())->get('/dashboard')->getContent();

        $this->assertStringContainsString('<meta name="robots" content="noindex, nofollow">', $html);
        $this->assertStringNotContainsString('og:title', $html);
    }

    public function test_sitemap_lists_public_pages_from_every_module(): void
    {
        $this->seed(LandingSeeder::class);
        $live = $this->devotional('Sudah Terbit', DevotionalStatus::Published);
        $draft = $this->devotional('Masih Draf', DevotionalStatus::Draft);

        $response = $this->get('/sitemap.xml')->assertOk();

        $this->assertStringStartsWith('application/xml', (string) $response->headers->get('Content-Type'));

        $xml = $response->getContent();

        foreach (['/', '/tentang', '/jadwal', '/kontak', '/renungan', '/galeri', '/kesaksian', '/doa'] as $path) {
            $this->assertStringContainsString('<loc>'.url($path).'</loc>', $xml);
        }

        $this->assertStringContainsString('<loc>'.url("/renungan/{$live->slug}").'</loc>', $xml);
        $this->assertStringNotContainsString($draft->slug, $xml);
        $this->assertStringNotContainsString('/admin', $xml);
    }

    public function test_robots_blocks_everything_outside_production(): void
    {
        $this->get('/robots.txt')
            ->assertOk()
            ->assertSee('Disallow: /', false)
            ->assertSee('Sitemap: '.url('/sitemap.xml'), false);
    }

    public function test_robots_in_production_only_blocks_private_areas(): void
    {
        $this->app['env'] = 'production';

        $body = $this->get('/robots.txt')->assertOk()->getContent();

        $this->assertStringContainsString("Disallow: /admin\n", $body);
        $this->assertStringContainsString("Disallow: /profil\n", $body);
        $this->assertStringNotContainsString("Disallow: /\n", $body);
    }

    /**
     * @return array<string, mixed>
     */
    private function schema(string $html): array
    {
        $this->assertSame(1, preg_match('#<script type="application/ld\+json">(.+?)</script>#s', $html, $match));

        return json_decode($match[1], true, flags: JSON_THROW_ON_ERROR);
    }

    private function devotional(string $title, DevotionalStatus $status): Devotional
    {
        $church = Church::query()->firstOrCreate(['slug' => 'ggp-elim'], ['name' => 'GGP Elim']);

        $devotional = new Devotional([
            'title' => $title,
            'scripture_ref' => 'Mazmur 23:1',
            'body' => 'Isi renungan.',
            'publish_date' => Devotional::today(),
        ]);
        $devotional->church_id = $church->id;
        $devotional->status = $status;
        $devotional->save();

        return $devotional;
    }
}
