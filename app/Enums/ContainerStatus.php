<?php

namespace App\Enums;

enum ContainerStatus: string
{
    case Draft     = 'draft';
    case Stuffing  = 'stuffing';
    case GateIn    = 'gate_in';
    case OnShip    = 'on_ship';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft     => 'Draf',
            self::Stuffing  => 'Stuffing',
            self::GateIn    => 'Gate-In',
            self::OnShip    => 'On Ship',
            self::Completed => 'Selesai',
            self::Cancelled => 'Dibatalkan',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft     => 'gray',
            self::Stuffing  => 'warning',
            self::GateIn    => 'primary',
            self::OnShip    => 'info',
            self::Completed => 'success',
            self::Cancelled => 'danger',
        };
    }
}
