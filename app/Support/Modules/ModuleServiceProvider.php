<?php

namespace App\Support\Modules;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use ReflectionClass;

/**
 * Base class for every feature module in app/Modules/<Name>.
 *
 * A module registers itself: its routes, migrations, config (feature flag),
 * permissions, seeders and sidebar items all live inside the module folder,
 * so adding or changing a module never requires editing a shared file.
 */
abstract class ModuleServiceProvider extends ServiceProvider
{
    /**
     * Unique kebab-case key, e.g. "prayer". Used for the config namespace
     * (modules.<key>), route names (<key>.*), the page folder
     * (resources/js/pages/<key>) and the icon folder (resources/js/modules/<key>).
     */
    abstract public function key(): string;

    /**
     * Human-readable module name (Bahasa Indonesia).
     */
    abstract public function name(): string;

    /**
     * Sidebar items for the logged-in panel.
     *
     * - route: a named route defined by this module
     * - icon: a key exported from resources/js/modules/<key>/icons.ts
     * - permission: hide the item unless the user can($permission)
     * - group: sidebar group label
     * - order: lower comes first (ties are sorted by title)
     *
     * @return list<array{title: string, route: string, icon?: string|null, permission?: string|null, group?: string, order?: int}>
     */
    public function navigation(): array
    {
        return [];
    }

    /**
     * Permission names this module introduces, e.g. "prayer.view".
     *
     * @return list<string>
     */
    public function permissions(): array
    {
        return [];
    }

    /**
     * Seeders that DatabaseSeeder runs for this module.
     *
     * @return list<class-string<Seeder>>
     */
    public function seeders(): array
    {
        return [];
    }

    public function enabled(): bool
    {
        return (bool) config("modules.{$this->key()}.enabled", true);
    }

    public function register(): void
    {
        $config = $this->path('config.php');

        if (is_file($config)) {
            $this->mergeConfigFrom($config, "modules.{$this->key()}");
        }

        $this->app->make(ModuleRegistry::class)->add($this);

        $this->registerModule();
    }

    public function boot(): void
    {
        // Migrations load even when the module is disabled, so every
        // environment ends up with the same schema.
        $this->loadMigrationsFrom($this->path('Database/Migrations'));

        if (! $this->enabled()) {
            return;
        }

        $routes = $this->path('routes/web.php');

        if (is_file($routes) && ! $this->app->routesAreCached()) {
            Route::middleware('web')->group($routes);
        }

        $this->bootModule();
    }

    /**
     * Hook for module-specific bindings.
     */
    protected function registerModule(): void {}

    /**
     * Hook for module-specific boot logic (policies, events, ...).
     */
    protected function bootModule(): void {}

    /**
     * Absolute path inside this module's folder.
     */
    public function path(string $path = ''): string
    {
        $directory = dirname((string) (new ReflectionClass($this))->getFileName());

        return $path === '' ? $directory : $directory.DIRECTORY_SEPARATOR.$path;
    }
}
