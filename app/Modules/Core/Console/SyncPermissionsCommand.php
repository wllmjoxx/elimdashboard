<?php

namespace App\Modules\Core\Console;

use App\Modules\Core\Actions\SyncRolesAndPermissions;
use App\Modules\Core\Enums\Role;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('core:sync-permissions')]
#[Description('Create module permissions and roles, and give each role its permissions')]
class SyncPermissionsCommand extends Command
{
    public function handle(SyncRolesAndPermissions $sync): int
    {
        $rows = [];

        foreach ($sync->handle() as $role => $permissions) {
            $rows[] = [Role::from($role)->label(), $role, implode(', ', $permissions) ?: '-'];
        }

        $this->table(['Role', 'Name', 'Permissions'], $rows);

        return self::SUCCESS;
    }
}
