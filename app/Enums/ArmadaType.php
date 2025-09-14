<?php

namespace App\Enums;

enum ArmadaType: string
{
    case Truck  = 'truck';
    case CcTw   = 'cc_tw';
    case Pickup = 'pickup';

    public function label(): string
    {
        return match ($this) {
            self::Truck  => 'Truck / Towing',
            self::CcTw   => 'CC / TW',
            self::Pickup => 'Pickup / Box',
        };
    }
}
