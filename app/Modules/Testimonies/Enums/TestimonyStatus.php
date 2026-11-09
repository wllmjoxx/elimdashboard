<?php

namespace App\Modules\Testimonies\Enums;

/**
 * Moderation status (PRD 4.7). "Perlu revisi" keeps a testimony out of view
 * while Admin edits it (for example to remove details that identify
 * someone), since the anonymous sender cannot be contacted.
 */
enum TestimonyStatus: string
{
    case Pending = 'pending';
    case Revision = 'revision';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu moderasi',
            self::Revision => 'Perlu revisi',
            self::Approved => 'Disetujui',
            self::Rejected => 'Ditolak',
        };
    }
}
