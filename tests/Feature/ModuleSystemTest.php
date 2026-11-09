<?php

namespace Tests\Feature;

use App\Models\User;
use App\Providers\ModulesServiceProvider;
use App\Support\Modules\ModuleRegistry;
use App\Support\Modules\ModuleServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;
use LogicException;
use Tests\TestCase;

class ModuleSystemTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_module_folder_is_discovered_and_registered(): void
    {
        $folders = array_map('basename', glob(app_path('Modules/*'), GLOB_ONLYDIR) ?: []);

        $this->assertCount(count($folders), ModulesServiceProvider::discover());
        $this->assertCount(count($folders), app(ModuleRegistry::class)->all());
    }

    public function test_module_keys_must_be_unique(): void
    {
        $registry = new ModuleRegistry;
        $registry->add($this->fakeModule('demo'));

        $this->expectException(LogicException::class);
        $registry->add(new class($this->app) extends ModuleServiceProvider
        {
            public function key(): string
            {
                return 'demo';
            }

            public function name(): string
            {
                return 'Another demo';
            }
        });
    }

    public function test_navigation_is_sorted_and_hides_items_the_user_cannot_access(): void
    {
        Route::get('/_demo/a', fn () => 'a')->name('demo.a');
        Route::get('/_demo/b', fn () => 'b')->name('demo.b');
        Route::get('/_demo/secret', fn () => 'secret')->name('demo.secret');
        Route::getRoutes()->refreshNameLookups();
        Gate::define('demo.secret', fn () => false);

        $registry = new ModuleRegistry;
        $registry->add($this->fakeModule('demo', [
            ['title' => 'B', 'route' => 'demo.b', 'order' => 20],
            ['title' => 'A', 'route' => 'demo.a', 'icon' => 'index', 'group' => 'Konten', 'order' => 10],
            ['title' => 'Secret', 'route' => 'demo.secret', 'permission' => 'demo.secret'],
        ]));

        $this->assertSame([
            ['module' => 'demo', 'title' => 'A', 'href' => '/_demo/a', 'icon' => 'index', 'group' => 'Konten'],
            ['module' => 'demo', 'title' => 'B', 'href' => '/_demo/b', 'icon' => null, 'group' => 'Menu'],
        ], $registry->navigation(User::factory()->make()));
    }

    public function test_module_navigation_is_shared_with_the_frontend(): void
    {
        $user = User::factory()->create();
        $expected = app(ModuleRegistry::class)->navigation($user);

        $this->assertNotEmpty($expected);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('navigation', count($expected))
                ->where('navigation.0.module', $expected[0]['module']));
    }

    /**
     * @param  list<array<string, mixed>>  $navigation
     */
    private function fakeModule(string $key, array $navigation = []): ModuleServiceProvider
    {
        return new class($this->app, $key, $navigation) extends ModuleServiceProvider
        {
            /**
             * @param  list<array<string, mixed>>  $items
             */
            public function __construct($app, private string $moduleKey, private array $items)
            {
                parent::__construct($app);
            }

            public function key(): string
            {
                return $this->moduleKey;
            }

            public function name(): string
            {
                return 'Demo';
            }

            public function navigation(): array
            {
                return $this->items;
            }
        };
    }
}
