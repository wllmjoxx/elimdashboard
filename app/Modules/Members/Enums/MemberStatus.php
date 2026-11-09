<?php

namespace App\Modules\Members\Enums;

/**
 * Account status (PRD 4.12 users.status). Only active accounts can sign in;
 * accounts are deactivated, never deleted (PRD 4.4).
 */
enum MemberStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Rejected = 'rejected';
    case Inactive = 'inactive';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu persetujuan',
            self::Active => 'Aktif',
            self::Rejected => 'Ditolak',
            self::Inactive => 'Nonaktif',
        };
    }

    /**
     * Shown on the login form when this account tries to sign in.
     */
    public function loginMessage(): ?string
    {
        return match ($this) {
            self::Active => null,
            self::Pending => 'Pendaftaran Anda masih menunggu persetujuan Admin. Kami akan mengabari lewat email.',
            self::Rejected => 'Pendaftaran Anda tidak disetujui. Hubungi sekretariat GGP Elim untuk informasi lebih lanjut.',
            self::Inactive => 'Akun Anda sedang dinonaktifkan. Hubungi sekretariat GGP Elim.',
        };
    }
}
