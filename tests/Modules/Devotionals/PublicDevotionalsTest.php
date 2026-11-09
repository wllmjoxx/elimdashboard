<?php

namespace Tests\Modules\Devotionals;

use App\Modules\Core\Models\Church;
use App\Modules\Devotionals\Enums\DevotionalStatus;
use App\Modules\Devotionals\Models\Devotional;
use App\Modules\Landing\Database\Seeders\LandingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PublicDevotionalsTest extends TestCase
{
    use RefreshDatabase;

    private Church $elim;

    protected function setUp(): void
    {
        parent::setUp();

        $this->elim = Church::query()->create(['name' => 'GGP Elim', 'slug' => 'ggp-elim']);
    }

    public function test_renungan_page_shows_the_newest_live_devotional_and_the_archive(): void
    {
        $this->published('Kemarin', Devotional::today()->subDay());
        $this->published('Hari Ini', Devotional::today());
        $this->published('Besok', Devotional::today()->addDay());
        $this->draft('Draf');

        $this->get('/renungan')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('devotionals/public/index')
                ->where('latest.title', 'Hari Ini')
                ->has('latest.body')
                ->has('archive.data', 2)
                ->where('archive.data.0.title', 'Hari Ini')
                ->where('archive.data.1.title', 'Kemarin'));
    }

    public function test_renungan_page_works_without_devotionals(): void
    {
        $this->get('/renungan')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('latest', null)->has('archive.data', 0));
    }

    public function test_devotional_page_links_to_neighbours(): void
    {
        $this->published('Satu', Devotional::today()->subDays(2));
        $middle = $this->published('Dua', Devotional::today()->subDay());
        $this->published('Tiga', Devotional::today());

        $this->get("/renungan/{$middle->slug}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('devotionals/public/show')
                ->where('devotional.title', 'Dua')
                ->where('devotional.body', 'Isi renungan.')
                ->where('older.title', 'Satu')
                ->where('newer.title', 'Tiga'));
    }

    public function test_drafts_scheduled_and_other_branches_are_not_public(): void
    {
        $draft = $this->draft('Draf');
        $scheduled = $this->published('Nanti', Devotional::today()->addDays(5));
        $other = Church::query()->create(['name' => 'Lain', 'slug' => 'lain']);
        $elsewhere = $this->published('Cabang Lain', Devotional::today(), $other);

        $this->get("/renungan/{$draft->slug}")->assertNotFound();
        $this->get("/renungan/{$scheduled->slug}")->assertNotFound();
        $this->get("/renungan/{$elsewhere->slug}")->assertNotFound();
    }

    public function test_scheduled_devotional_goes_live_on_its_date(): void
    {
        $scheduled = $this->published('Minggu Depan', Devotional::today()->addDays(7));

        Carbon::setTestNow(now()->addDays(8));

        $this->get("/renungan/{$scheduled->slug}")->assertOk();

        Carbon::setTestNow();
    }

    public function test_homepage_teaser_uses_the_newest_devotional(): void
    {
        $this->seed(LandingSeeder::class);
        $live = $this->published('Kasih yang Sabar', Devotional::today());

        $this->get(route('home'))
            ->assertInertia(function (Assert $page) use ($live) {
                $section = collect($page->toArray()['props']['sections'])->firstWhere('key', 'devotional');

                $this->assertSame('Kasih yang Sabar', $section['content']['title']);
                $this->assertFalse($section['content']['is_sample']);
                $this->assertSame(route('devotionals.public.show', $live->slug), $section['content']['url']);
                $this->assertSame('Renungan Harian', $section['content']['label']);
            });
    }

    public function test_homepage_teaser_falls_back_to_the_sample(): void
    {
        $this->seed(LandingSeeder::class);

        $this->get(route('home'))
            ->assertInertia(function (Assert $page) {
                $section = collect($page->toArray()['props']['sections'])->firstWhere('key', 'devotional');

                $this->assertTrue($section['content']['is_sample']);
            });
    }

    private function published(string $title, Carbon $date, ?Church $church = null): Devotional
    {
        return $this->make($title, $date, DevotionalStatus::Published, $church);
    }

    private function draft(string $title): Devotional
    {
        return $this->make($title, Devotional::today(), DevotionalStatus::Draft);
    }

    private function make(string $title, Carbon $date, DevotionalStatus $status, ?Church $church = null): Devotional
    {
        $devotional = new Devotional([
            'title' => $title,
            'scripture_ref' => 'Mazmur 23:1',
            'body' => 'Isi renungan.',
            'publish_date' => $date,
        ]);
        $devotional->church_id = ($church ?? $this->elim)->id;
        $devotional->status = $status;
        $devotional->save();

        return $devotional;
    }
}
