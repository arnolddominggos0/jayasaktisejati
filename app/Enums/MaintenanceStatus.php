<?php

namespace App\Enums;

enum MaintenanceStatus: string
{
    case Scheduled  = 'scheduled';
    case InProgress = 'in_progress';
    case Closed     = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Scheduled  => 'Terjadwal',
            self::InProgress => 'Berjalan',
            self::Closed     => 'Selesai',
        };
    }

    public static function coerce(self|string $value): self
    {
        return $value instanceof self ? $value : self::from($value);
    }
}
