<?php

namespace App\Modules\Prayer\Enums;

enum PrayerCategory: string
{
    case Health = 'health';
    case Family = 'family';
    case Work = 'work';
    case Study = 'study';
    case Salvation = 'salvation';
    case Ministry = 'ministry';
    case Thanksgiving = 'thanksgiving';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Health => 'Kesehatan',
            self::Family => 'Keluarga',
            self::Work => 'Pekerjaan dan keuangan',
            self::Study => 'Studi',
            self::Salvation => 'Keselamatan dan pertobatan',
            self::Ministry => 'Pelayanan',
            self::Thanksgiving => 'Ucapan syukur',
            self::Other => 'Lainnya',
        };
    }
}
