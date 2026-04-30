<?php

namespace App\Enums;

enum APDStatus: string
{
    case Available   = 'available';
    case InUse       = 'in_use';
    case Maintenance = 'maintenance';
    case Retired     = 'retired';

    public function label(): string
    {
        return match ($this) {
            self::Available   => 'Tersedia',
            self::InUse       => 'Sedang Digunakan',
            self::Maintenance => 'Perawatan',
            self::Retired     => 'Dikeluarkan',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Available   => 'success',
            self::InUse       => 'info',
            self::Maintenance => 'warning',
            self::Retired     => 'gray',
        };
    }
}
