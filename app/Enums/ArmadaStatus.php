<?php

namespace App\Enums;

enum ArmadaStatus: string
{
    case Available   = 'available';
    case OnDuty      = 'on_duty';
    case Maintenance = 'maintenance';
    case Standby     = 'standby';

    public function label(): string
    {
        return match ($this) {
            self::Available   => 'Siap Pakai',
            self::OnDuty      => 'Sedang Bertugas',
            self::Maintenance => 'Perawatan',
            self::Standby     => 'Standby',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Available   => 'success',
            self::OnDuty      => 'info',
            self::Maintenance => 'warning',
            self::Standby     => 'gray',
        };
    }
}
