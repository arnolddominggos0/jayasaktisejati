<?php

namespace App\Enums;

enum ArmadaType: string
{
    case Truck      = 'truck';
    case CarCarrier = 'car_carrier';
    case Towing     = 'towing';

    public function label(): string
    {
        return match ($this) {
            self::Truck      => 'Truck',
            self::CarCarrier => 'Car Carrier',
            self::Towing     => 'Towing',
        };
    }
}
