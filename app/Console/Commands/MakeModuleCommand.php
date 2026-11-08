<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class MakeModuleCommand extends Command
{
    protected $signature = 'make:module
        {name : StudlyCase module name, e.g. Prayer}
        {--title= : Display name in Bahasa Indonesia, e.g. "Doa Mingguan"}
        {--icon=LayoutGrid : lucide-react icon for the sidebar item}';

    protected $description = 'Create a self-registering feature module (provider, routes, page, icon, seeder, test)';

    public function handle(Filesystem $files): int
    {
        $name = Str::studly((string) $this->argument('name'));
        $key = Str::kebab($name);
        $title = (string) ($this->option('title') ?: Str::headline($name));
        $icon = Str::studly((string) $this->option('icon'));

        $targets = [
            'provider.stub' => app_path("Modules/{$name}/{$name}ServiceProvider.php"),
            'config.stub' => app_path("Modules/{$name}/config.php"),
            'routes.stub' => app_path("Modules/{$name}/routes/web.php"),
            'controller.stub' => app_path("Modules/{$name}/Http/Controllers/{$name}Controller.php"),
            'seeder.stub' => app_path("Modules/{$name}/Database/Seeders/{$name}Seeder.php"),
            'gitkeep.stub' => app_path("Modules/{$name}/Database/Migrations/.gitkeep"),
            'page.stub' => resource_path("js/pages/{$key}/index.tsx"),
            'icons.stub' => resource_path("js/modules/{$key}/icons.ts"),
            'test.stub' => base_path("tests/Modules/{$name}/{$name}ModuleTest.php"),
        ];

        if ($files->isDirectory(app_path("Modules/{$name}"))) {
            $this->components->error("Module [{$name}] already exists.");

            return self::FAILURE;
        }

        foreach ($targets as $target) {
            if ($files->exists($target)) {
                $this->components->error("File already exists: {$this->relative($target)}");

                return self::FAILURE;
            }
        }

        $replacements = [
            '{{ Name }}' => $name,
            '{{ key }}' => $key,
            '{{ KEY }}' => Str::upper(Str::snake($name)),
            '{{ title }}' => str_replace("'", "\\'", $title),
            '{{ Icon }}' => $icon,
        ];

        foreach ($targets as $stub => $target) {
            $files->ensureDirectoryExists(dirname($target));
            $files->put($target, strtr($files->get(base_path("stubs/module/{$stub}")), $replacements));
            $this->components->twoColumnDetail($this->relative($target), '<fg=green;options=bold>CREATED</>');
        }

        $this->newLine();
        $this->components->info("Module [{$name}] created. It is registered automatically; no shared file needs editing.");
        $this->components->bulletList([
            "Model:     php artisan make:model 'App\\Modules\\{$name}\\Models\\Example'",
            "Migration: php artisan make:migration create_examples_table --path=app/Modules/{$name}/Database/Migrations",
            "Test:      php artisan test tests/Modules/{$name}",
        ]);

        return self::SUCCESS;
    }

    private function relative(string $path): string
    {
        return ltrim(Str::after($path, base_path()), DIRECTORY_SEPARATOR);
    }
}
