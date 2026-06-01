<?php

namespace App\Enums;

enum ShipmentStatus: string
{
    case Draft     = 'draft';
    case Pending   = 'pending';
    case Pickup    = 'pickup';
    case Transit   = 'transit';
    case Delivered = 'delivered';
    case Hold      = 'hold';
    case Cancelled = 'cancelled';

    public static function default(): self
    {
        return self::Draft;
    }

    public function label(): string
    {
        return match ($this) {
            self::Draft     => 'Draf',
            self::Pending   => 'Menunggu',
            self::Pickup    => 'Penjemputan',
            self::Transit   => 'Dalam Perjalanan',
            self::Delivered => 'Terkirim',
            self::Hold      => 'Ditahan',
            self::Cancelled => 'Dibatalkan',
        };
    }

    public static function inProgress(): array
    {
        return [self::Pending, self::Pickup, self::Transit];
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Pending => 'warning',
            self::Pickup => 'info',
            self::Transit => 'warning',
            self::Delivered => 'success',
            self::Hold => 'danger',
            self::Cancelled => 'danger',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Draft => 'heroicon-o-document',
            self::Pending => 'heroicon-o-clock',
            self::Pickup => 'heroicon-o-map-pin',
            self::Transit => 'heroicon-o-truck',
            self::Delivered => 'heroicon-o-check-circle',
            self::Hold => 'heroicon-o-exclamation-triangle',
            self::Cancelled => 'heroicon-o-x-circle',
        };
    }

    public static function notInProgress(): array
    {
        return [self::Draft, self::Delivered, self::Hold, self::Cancelled];
    }

    public static function completed(): array
    {
        return [self::Delivered, self::Cancelled];
    }

    public static function active(): array
    {
        return self::inProgress();
    }
}
