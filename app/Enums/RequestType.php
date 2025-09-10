<?php

namespace App\Enums;

enum RequestType: string
{
    case SPPB_DO = 'sppb_do';
    case WA_TELP = 'wa_telp';
    case WALK_IN = 'walk_in';

    public function label(): string
    {
        return match ($this) {
            self::SPPB_DO => 'SPPB/DO',
            self::WA_TELP => 'WA/TELP',
            self::WALK_IN => 'WALK - IN',
        };
    }
}
