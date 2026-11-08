<?php

namespace App\Support\Modules;

use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Seeder;
use LogicException;

/**
 * Collects every registered module so shared parts of the app (sidebar,
 * seeders, permission sync) can read from modules instead of a shared list.
 */
final class ModuleRegistry
{
    /**
     * @var array<string, ModuleServiceProvider>
     */
    private array $modules = [];

    public function add(ModuleServiceProvider $module): void
    {
        $key = $module->key();
        $existing = $this->modules[$key] ?? null;

        if ($existing !== null && $existing::class !== $module::class) {
            throw new LogicException(sprintf(
                'Module key [%s] is used by both %s and %s.',
                $key,
                $existing::class,
                $module::class,
            ));
        }

        $this->modules[$key] = $module;
    }

    /**
     * @return array<string, ModuleServiceProvider>
     */
    public function all(): array
    {
        return $this->modules;
    }

    /**
     * @return array<string, ModuleServiceProvider>
     */
    public function enabled(): array
    {
        return array_filter($this->modules, fn (ModuleServiceProvider $module) => $module->enabled());
    }

    public function find(string $key): ?ModuleServiceProvider
    {
        return $this->modules[$key] ?? null;
    }

    /**
     * @return list<string>
     */
    public function permissions(): array
    {
        $permissions = [];

        foreach ($this->modules as $module) {
            array_push($permissions, ...$module->permissions());
        }

        $permissions = array_values(array_unique($permissions));
        sort($permissions);

        return $permissions;
    }

    /**
     * @return list<class-string<Seeder>>
     */
    public function seeders(): array
    {
        $seeders = [];

        foreach ($this->modules as $module) {
            array_push($seeders, ...$module->seeders());
        }

        return $seeders;
    }

    /**
     * Sidebar items from enabled modules that the given user may see.
     *
     * @return list<array{module: string, title: string, href: string, icon: string|null, group: string}>
     */
    public function navigation(?Authenticatable $user): array
    {
        $items = [];

        foreach ($this->enabled() as $key => $module) {
            foreach ($module->navigation() as $item) {
                $permission = $item['permission'] ?? null;

                if ($permission !== null && ! ($user instanceof Authorizable && $user->can($permission))) {
                    continue;
                }

                $items[] = [
                    'module' => $key,
                    'title' => $item['title'],
                    'href' => route($item['route'], absolute: false),
                    'icon' => $item['icon'] ?? null,
                    'group' => $item['group'] ?? 'Menu',
                    'order' => $item['order'] ?? 100,
                ];
            }
        }

        usort($items, fn (array $a, array $b) => [$a['order'], $a['title']] <=> [$b['order'], $b['title']]);

        return array_map(function (array $item) {
            unset($item['order']);

            return $item;
        }, $items);
    }
}
