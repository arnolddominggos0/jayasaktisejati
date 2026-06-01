<?php

namespace App\Enums;

enum AttendanceStatus: string
{
    case Present = 'present';
    case Absent  = 'absent';
    case Sick    = 'sick';
    case Leave   = 'leave';

    public function label(): string
    {
        return match ($this) {
            self::Present => 'Hadir',
            self::Absent  => 'Tidak Hadir',
            self::Sick    => 'Sakit',
            self::Leave   => 'Izin',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Present => 'success',
            self::Absent  => 'danger',
            self::Sick    => 'warning',
            self::Leave   => 'info',
        };
    }
}
