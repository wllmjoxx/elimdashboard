<?php

namespace App\Modules\Core\Enums;

/**
 * Roles from PRD 4.2. The value is the spatie role name; the label is shown in
 * the UI. New roles are added here and in config modules.core.role_permissions.
 */
enum Role: string
{
    case Superadmin = 'superadmin';
    case Admin = 'admin';
    case PrayerTeam = 'prayer-team';
    case Servant = 'servant';
    case Member = 'member';

    public function label(): string
    {
        return match ($this) {
            self::Superadmin => 'Superadmin',
            self::Admin => 'Admin',
            self::PrayerTeam => 'Tim Doa',
            self::Servant => 'Pelayan',
            self::Member => 'Jemaat',
        };
    }
}
