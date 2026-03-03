<?php

namespace App\Enums;

enum VoyageOperationalStatus: string
{
    case SCHEDULED = 'scheduled';
    case SAILING   = 'sailing';
    case DELAYED   = 'delayed';
    case COMPLETED = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::SCHEDULED => 'Terjadwal',
            self::SAILING   => 'Sedang Berlayar',
            self::DELAYED   => 'Terlambat',
            self::COMPLETED => 'Selesai',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::SCHEDULED => 'bg-gray-600 text-white',
            self::SAILING   => 'bg-blue-600 text-white',
            self::DELAYED   => 'bg-red-600 text-white',
            self::COMPLETED => 'bg-green-600 text-white',
        };
    }
}
