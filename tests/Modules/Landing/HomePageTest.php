<?php

namespace Tests\Modules\Landing;

use App\Modules\Landing\Database\Seeders\LandingSeeder;
use App\Modules\Landing\Models\ServiceSchedule;
use App\Modules\Landing\Models\SiteSection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_see_the_seeded_homepage(): void
    {
        $this->seed(LandingSeeder::class);

        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('landing/public/home')
                // The pastor section waits hidden until Admin fills it in.
                ->has('sections', count(SiteSection::HOME_KEYS) - 1)
                ->where('sections.0.key', 'hero')
                ->where('sections.0.content.slides.0.title', 'Yesus Kristus Imam Besar Agung kita')
                ->where('sections.0.content.primary_cta.href', '/jadwal')
                ->where('sections.1.key', 'events')
                ->where('sections.2.key', 'segment_1')
                ->where('sections.2.content.image_url', null)
                ->missing('sections.2.content.image')
                ->has('scheduleGroups', 3)
                ->where('scheduleGroups.0.label', 'Ibadah Raya')
                ->where('scheduleGroups.0.items.0.day', 'Minggu')
                ->where('scheduleGroups.0.items.0.time', '08.00')
                ->where('scheduleGroups.2.label', 'Menara Doa'));
    }

    public function test_homepage_leaves_page_only_sections_out(): void
    {
        $this->seed(LandingSeeder::class);

        $this->get(route('home'))
            ->assertInertia(function (Assert $page) {
                $keys = array_column($page->toArray()['props']['sections'], 'key');

                $this->assertSame(array_values(array_diff(SiteSection::HOME_KEYS, ['pastor'])), $keys);
                $this->assertNotContains('about', $keys);
                $this->assertNotContains('contact', $keys);
            });
    }

    public function test_sections_follow_sort_order_and_hide_inactive_or_unknown_ones(): void
    {
        SiteSection::query()->create(['key' => 'testimonies', 'content' => ['title' => 'Kesaksian'], 'sort_order' => 10]);
        SiteSection::query()->create(['key' => 'hero', 'content' => ['slides' => []], 'sort_order' => 5]);
        SiteSection::query()->create(['key' => 'gallery', 'content' => ['title' => 'Galeri'], 'is_active' => false]);
        SiteSection::query()->create(['key' => 'not-a-section', 'content' => []]);

        $this->get(route('home'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('sections', 2)
                ->where('sections.0.key', 'hero')
                ->where('sections.1.key', 'testimonies'));
    }

    public function test_schedule_groups_skip_empty_categories_and_inactive_items(): void
    {
        ServiceSchedule::query()->create(['category' => 'doa', 'name' => 'Menara Doa', 'day' => 2, 'start_time' => '19:00']);
        ServiceSchedule::query()->create(['category' => 'raya', 'name' => 'Ibadah Lama', 'day' => 0, 'start_time' => '06:00', 'is_active' => false]);

        $this->get(route('home'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('scheduleGroups', 1)
                ->where('scheduleGroups.0.category', 'doa')
                ->where('scheduleGroups.0.items.0.day', 'Selasa')
                ->where('scheduleGroups.0.items.0.time', '19.00'));
    }

    public function test_seeder_can_run_twice_without_duplicates(): void
    {
        $this->seed(LandingSeeder::class);
        $this->seed(LandingSeeder::class);

        $this->assertSame(count(SiteSection::KEYS), SiteSection::query()->count());
        $this->assertSame(8, ServiceSchedule::query()->count());
    }

    public function test_seeder_keeps_segments_edited_by_admin(): void
    {
        $this->seed(LandingSeeder::class);

        $segment = SiteSection::query()->where('key', 'segment_2')->firstOrFail();
        $segment->update(['content' => [...$segment->content, 'title' => 'Diubah Admin']]);

        $this->seed(LandingSeeder::class);

        $this->assertSame('Diubah Admin', $segment->fresh()->content['title']);
    }
}
