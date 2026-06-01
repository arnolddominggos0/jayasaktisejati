<?php

namespace App\Enums;

enum ShipmentMode: string
{
    case Sea  = 'sea';
    case Land = 'land';

    public function label(): string
    {
        return $this === self::Sea ? 'Laut' : 'Darat';
    }
}
