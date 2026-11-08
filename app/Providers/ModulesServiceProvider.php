<?php

namespace App\Providers;

use App\Support\Modules\ModuleRegistry;
use App\Support\Modules\ModuleServiceProvider;
use Illuminate\Support\ServiceProvider;

/**
 * Discovers and registers every module in app/Modules/<Name>/<Name>ServiceProvider.php.
 *
 * Adding a module never touches bootstrap/providers.php or any other shared file.
 */
class ModulesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ModuleRegistry::class);

        foreach (self::discover() as $provider) {
            $this->app->register($provider);
        }
    }

    /**
     * @return list<class-string<ModuleServiceProvider>>
     */
    public static function discover(): array
    {
        $providers = [];

        foreach (glob(app_path('Modules/*/*ServiceProvider.php')) ?: [] as $file) {
            $class = sprintf('App\\Modules\\%s\\%s', basename(dirname($file)), basename($file, '.php'));

            if (is_subclass_of($class, ModuleServiceProvider::class)) {
                $providers[] = $class;
            }
        }

        sort($providers);

        return $providers;
    }
}
