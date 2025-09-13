<?php

namespace App\Enums;

enum ManpowerRole: string
{
    case Driver = 'driver';
    case Helper = 'helper';
    case Operator = 'operator';
    case Admin = 'admin';

    public function label(): string
    {
        return match($this) {
            self::Driver   => 'Supir',
            self::Helper   => 'Kernet',
            self::Operator => 'Operator',
            self::Admin    => 'Admin Armada/MP',
        };
    }
}
