<?php

namespace App\Enums;

enum VesselCheckStatus: string
{
    case ETD_DELAY   = 'ETD_DELAY';
    case IN_PROGRESS = 'IN_PROGRESS';
    case RESOLVED    = 'RESOLVED';
    case COMPLETED   = 'COMPLETED';

    public function label(): string
    {
        return match ($this) {
            self::ETD_DELAY   => 'Potensi Delay',
            self::IN_PROGRESS => 'Sedang Ditangani',
            self::RESOLVED    => 'Solusi Ditentukan',
            self::COMPLETED   => 'Selesai',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::ETD_DELAY   => 'warning',
            self::IN_PROGRESS => 'info',
            self::RESOLVED    => 'success',
            self::COMPLETED   => 'gray',
        };
    }
}
