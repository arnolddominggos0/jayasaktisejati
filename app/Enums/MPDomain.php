<?php

namespace App\Enums;

enum MPDomain: string
{
    case SeaFreight   = 'sea_freight';
    case LandTrucking = 'land_trucking';

    public function label(): string
    {
        return match ($this) {
            self::SeaFreight   => 'Lapangan – Laut',
            self::LandTrucking => 'Lapangan – Darat',
        };
    }
}
