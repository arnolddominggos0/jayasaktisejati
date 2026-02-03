<?php

namespace App\Enums;

enum VesselCheckStatus: string
{
    case ON_SCHEDULE = 'ON_SCHEDULE';
    case ETD_DELAY   = 'ETD_DELAY';
    case IN_PROGRESS = 'IN_PROGRESS';
    case RESOLVED    = 'RESOLVED';
    case COMPLETED   = 'COMPLETED';

    public function label(): string
    {
        return match ($this) {
            self::ON_SCHEDULE => 'Sesuai Jadwal',
            self::ETD_DELAY => 'Berpotensi Terlambat',
            self::IN_PROGRESS => 'Sedang Berjalan',
            self::RESOLVED => 'Diselesaikan',
            self::COMPLETED => 'Selesai',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::ON_SCHEDULE => 'bg-green-100 text-green-700',
            self::ETD_DELAY => 'bg-yellow-100 text-yellow-700',
            self::IN_PROGRESS => 'bg-blue-100 text-blue-700',
            self::RESOLVED => 'bg-purple-100 text-purple-700',
            self::COMPLETED => 'bg-gray-100 text-gray-700',
        };
    }
}
