<?php

namespace App\Enums;

enum VesselCheckStatus: string
{
    case ON_SCHEDULE = 'on_schedule';
    case POTENTIAL_DELAY = 'potential_delay';
    case DELAYED = 'delayed';

    public function label(): string
    {
        return match ($this) {
            self::ON_SCHEDULE => 'Sesuai Jadwal',
            self::POTENTIAL_DELAY => 'Berpotensi Terlambat',
            self::DELAYED => 'Terlambat',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::ON_SCHEDULE => 'bg-green-100 text-green-700',
            self::POTENTIAL_DELAY => 'bg-yellow-100 text-yellow-700',
            self::DELAYED => 'bg-red-100 text-red-700',
        };
    }
}
