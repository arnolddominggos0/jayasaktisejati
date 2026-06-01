<?php

namespace App\Enums;

enum ServiceType: string
{
    case SeaFreight   = 'sea_freight';
    case LandTrucking = 'land_trucking';
    case CarCarrier   = 'car_carrier';

    public function label(): string
    {
        return match ($this) {
            self::SeaFreight   => 'Angkutan Laut',
            self::LandTrucking => 'Trucking Darat',
            self::CarCarrier   => 'Car Carrier / Towing',
        };
    }
}
