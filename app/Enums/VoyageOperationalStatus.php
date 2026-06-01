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
            self::SAILING   => 'Berlayar',
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

    public function canTransitionTo(self $target): bool
    {
        return match ($this) {
            self::SCHEDULED => in_array($target, [self::SAILING, self::DELAYED], true),
            self::SAILING  => in_array($target, [self::COMPLETED, self::DELAYED], true),
            self::DELAYED  => in_array($target, [self::SAILING, self::COMPLETED], true),
            self::COMPLETED => false,
        };
    }

    public function isActive(): bool
    {
        return in_array($this, [self::SCHEDULED, self::SAILING, self::DELAYED], true);
    }
}
