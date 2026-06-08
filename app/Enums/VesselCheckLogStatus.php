<?php

namespace App\Enums;

enum VesselCheckLogStatus: string
{
    case OK   = 'ok';
    case LATE = 'late';

    public function label(): string
    {
        return match ($this) {
            self::OK   => 'OK',
            self::LATE => 'Late',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::OK   => 'success',
            self::LATE => 'danger',
        };
    }

    public function isLate(): bool
    {
        return $this === self::LATE;
    }

    public function isOk(): bool
    {
        return $this === self::OK;
    }
}
