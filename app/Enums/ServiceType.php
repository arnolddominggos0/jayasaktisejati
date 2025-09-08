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
            self::SeaFreight   => 'Pengiriman Laut',
            self::LandTrucking => 'Pengiriman Darat (Trucking)',
            self::CarCarrier   => 'Car Carrier / Towing',
        };
    }
}