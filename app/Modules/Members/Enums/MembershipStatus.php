<?php

namespace App\Modules\Members\Enums;

/**
 * Church membership recorded by Admin (PRD 4.5).
 */
enum MembershipStatus: string
{
    case Sympathizer = 'sympathizer';
    case Member = 'member';
    case Moved = 'moved';

    public function label(): string
    {
        return match ($this) {
            self::Sympathizer => 'Simpatisan',
            self::Member => 'Anggota jemaat',
            self::Moved => 'Pindah gereja',
        };
    }
}
