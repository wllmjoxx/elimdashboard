<?php

namespace Tests\Modules\Landing;

use App\Modules\Landing\Database\Seeders\LandingSeeder;
use App\Modules\Landing\Models\SiteSection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{0: string, 1: string, 2: list<string>}>
     */
    public static function pages(): array
    {
        return [
            'tentang' => ['landing.about', '/tentang', ['about', 'mission']],
            'jadwal' => ['landing.schedule', '/jadwal', ['schedule']],
            'kontak' => ['landing.contact', '/kontak', ['contact']],
        ];
    }

    /**
     * @param  list<string>  $keys
     */
    #[DataProvider('pages')]
    public function test_each_menu_item_has_its_own_page(string $route, string $path, array $keys): void
    {
        $this->seed(LandingSeeder::class);

        $this->assertSame($path, route($route, absolute: false));

        $this->get($path)
            ->assertOk()
            ->assertInertia(function (Assert $page) use ($keys) {
                $page->component('landing/public/page')->has('title')->has('description');

                $this->assertSame($keys, array_column($page->toArray()['props']['sections'], 'key'));
            });
    }

    public function test_schedule_page_includes_the_schedule(): void
    {
        $this->seed(LandingSeeder::class);

        $this->get('/jadwal')
            ->assertInertia(fn (Assert $page) => $page->has('scheduleGroups', 3));
    }

    public function test_page_is_missing_when_its_sections_are_hidden(): void
    {
        $this->seed(LandingSeeder::class);

        SiteSection::query()->where('key', 'contact')->update(['is_active' => false]);

        $this->get('/kontak')->assertNotFound();
    }
}
