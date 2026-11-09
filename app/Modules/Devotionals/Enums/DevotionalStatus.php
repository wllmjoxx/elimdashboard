<?php

namespace App\Modules\Devotionals\Enums;

/**
 * PRD 4.12 devotionals.status. A published devotional with a future
 * publish date is "scheduled" (shown as Terjadwal) until that date.
 */
enum DevotionalStatus: string
{
    case Draft = 'draft';
    case Published = 'published';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draf',
            self::Published => 'Terbit',
        };
    }
}
