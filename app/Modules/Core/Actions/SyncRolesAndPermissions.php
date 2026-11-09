<?php

namespace App\Modules\Core\Actions;

use App\Modules\Core\Enums\Role as RoleName;
use App\Support\Modules\ModuleRegistry;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Makes the database match the code: creates every permission that modules
 * declare, every role in the Role enum, and gives each role the permissions
 * from config modules.core.role_permissions. Safe to run on every deploy.
 */
class SyncRolesAndPermissions
{
    public function __construct(
        private readonly ModuleRegistry $modules,
        private readonly PermissionRegistrar $registrar,
    ) {}

    /**
     * @return array<string, list<string>> permission names per role
     */
    public function handle(): array
    {
        $this->registrar->forgetCachedPermissions();

        $permissions = $this->modules->permissions();

        foreach ($permissions as $name) {
            Permission::findOrCreate($name, 'web');
        }

        foreach (RoleName::cases() as $roleName) {
            Role::findOrCreate($roleName->value, 'web');
        }

        // Spatie clears its cache from model events, which DatabaseSeeder
        // switches off (WithoutModelEvents), so clear it by hand.
        $this->registrar->forgetCachedPermissions();

        $result = [];

        foreach (RoleName::cases() as $roleName) {
            $role = Role::findByName($roleName->value, 'web');

            if ($roleName === RoleName::Superadmin) {
                // Passes every check through Gate::before.
                $result[$roleName->value] = ['*'];

                continue;
            }

            $granted = $this->match(config("modules.core.role_permissions.{$roleName->value}", []), $permissions);
            $role->syncPermissions($granted);
            $result[$roleName->value] = $granted;
        }

        $this->registrar->forgetCachedPermissions();

        return $result;
    }

    /**
     * @param  list<string>  $patterns
     * @param  list<string>  $permissions
     * @return list<string>
     */
    private function match(array $patterns, array $permissions): array
    {
        $include = array_filter($patterns, fn (string $pattern) => ! str_starts_with($pattern, '!'));
        $exclude = array_map(
            fn (string $pattern) => substr($pattern, 1),
            array_filter($patterns, fn (string $pattern) => str_starts_with($pattern, '!')),
        );

        return array_values(array_filter(
            $permissions,
            fn (string $name) => Str::is($include, $name) && ! Str::is($exclude, $name),
        ));
    }
}
