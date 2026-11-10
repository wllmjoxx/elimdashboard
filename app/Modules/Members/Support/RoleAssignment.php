<?php

namespace App\Modules\Members\Support;

use App\Models\User;
use App\Modules\Core\Enums\Role;

/**
 * Who may give which role (PRD 4.2): Admin manages Pelayan and Tim Doa
 * accounts in their branch; only Superadmin hands out Admin and Superadmin
 * or changes an Admin's role.
 */
class RoleAssignment
{
    /**
     * @return list<Role>
     */
    public function assignable(User $actor): array
    {
        if ($actor->hasRole(Role::Superadmin->value)) {
            return Role::cases();
        }

        if ($actor->can('members.roles')) {
            return [Role::Member, Role::Servant, Role::PrayerTeam];
        }

        return [];
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public function options(User $actor): array
    {
        return array_map(fn (Role $role) => ['value' => $role->value, 'label' => $role->label()], $this->assignable($actor));
    }

    public function mayAssign(User $actor, Role $role): bool
    {
        return in_array($role, $this->assignable($actor), true);
    }

    /**
     * Admin cannot change the role of another Admin or a Superadmin.
     */
    public function protects(User $actor, User $target): bool
    {
        return ! $actor->hasRole(Role::Superadmin->value)
            && $target->hasAnyRole([Role::Admin->value, Role::Superadmin->value]);
    }

    /**
     * The account's main role (accounts hold one role).
     */
    public function current(User $user): ?Role
    {
        foreach (Role::cases() as $role) {
            if ($user->hasRole($role->value)) {
                return $role;
            }
        }

        return null;
    }
}
