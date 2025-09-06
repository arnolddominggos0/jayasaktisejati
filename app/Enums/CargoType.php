<?php

namespace App\Enums;

enum CargoType: string
{
    case Vehicle = 'vehicle';
    case General = 'general';

    public function label(): string
    {
        return $this === self::Vehicle ? 'Unit Kendaraan' : 'General Cargo';
    }
}
