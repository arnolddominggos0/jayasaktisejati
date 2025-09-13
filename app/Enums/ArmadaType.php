<?php

namespace App\Enums;

enum ArmadaType: string
{
    case Truck = 'truck';
    case CcTw  = 'cc_tw';
    case Container = 'container';
    case Kapal = 'kapal';

    public function label(): string
    {
        return match($this) {
            self::Truck     => 'Truck / Towing',
            self::CcTw      => 'CC / TW',
            self::Container => 'Container',
            self::Kapal     => 'Kapal Laut',
        };
    }
}