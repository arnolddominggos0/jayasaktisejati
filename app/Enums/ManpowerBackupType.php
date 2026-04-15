<?php

namespace App\Enums;

enum ManpowerBackupType: string
{
    case Internal = 'internal';
    case External = 'external';
    case None = 'none';

    public function label(): string
    {
        return match ($this) {
            self::Internal => 'MP Backup Internal',
            self::External => 'MP Luar',
            self::None => 'Tidak Ada',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Internal => 'success',
            self::External => 'warning',
            self::None => 'danger',
        };
    }
}
