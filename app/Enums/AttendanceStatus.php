<?php

namespace App\Enums;

enum AttendanceStatus: string
{
    case Present = 'present';
    case Late    = 'late';
    case Absent  = 'absent';
    case Sick    = 'sick';

    public function label(): string
    {
        return match ($this) {
            self::Present => 'Hadir',
            self::Late    => 'Terlambat',
            self::Absent  => 'Alfa',
            self::Sick    => 'Sakit',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Present => 'success',
            self::Late    => 'warning',
            self::Absent  => 'danger',
            self::Sick    => 'gray',
        };
    }
}
