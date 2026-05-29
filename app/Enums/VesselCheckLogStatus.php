<?php

namespace App\Enums;

enum VesselCheckLogStatus: string
{
    case ON_SCHEDULE     = 'on_schedule';
    case POTENTIAL_DELAY = 'potential_delay';

    public function label(): string
    {
        return match ($this) {
            self::ON_SCHEDULE     => 'Sesuai Jadwal',
            self::POTENTIAL_DELAY => 'Potensi Delay',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::ON_SCHEDULE     => 'success',
            self::POTENTIAL_DELAY => 'warning',
        };
    }

    public function isPotentialDelay(): bool
    {
        return $this === self::POTENTIAL_DELAY;
    }

    public function isOnSchedule(): bool
    {
        return $this === self::ON_SCHEDULE;
    }
}
