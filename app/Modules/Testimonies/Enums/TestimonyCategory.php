<?php

namespace App\Modules\Testimonies\Enums;

enum TestimonyCategory: string
{
    case Healing = 'healing';
    case Family = 'family';
    case Provision = 'provision';
    case Salvation = 'salvation';
    case AnsweredPrayer = 'answered_prayer';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Healing => 'Kesembuhan',
            self::Family => 'Pemulihan keluarga',
            self::Provision => 'Pekerjaan dan berkat',
            self::Salvation => 'Pertobatan',
            self::AnsweredPrayer => 'Doa terjawab',
            self::Other => 'Lainnya',
        };
    }
}
