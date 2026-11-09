<?php

namespace App\Modules\Prayer\Enums;

/**
 * PRD 4.12 prayer_requests.status.
 */
enum PrayerStatus: string
{
    case New = 'new';
    case Prayed = 'prayed';
    case Answered = 'answered';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::New => 'Baru',
            self::Prayed => 'Didoakan',
            self::Answered => 'Terjawab',
            self::Archived => 'Diarsipkan',
        };
    }
}
