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
            self::SCHEDULED => 'bg-gray-100 text-gray-700',
            self::SAILING   => 'bg-blue-100 text-blue-700',
            self::DELAYED   => 'bg-red-100 text-red-700',
            self::COMPLETED => 'bg-green-100 text-green-700',
        };
    }
}