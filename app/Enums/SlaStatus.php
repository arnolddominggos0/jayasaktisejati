<?php

namespace App\Enums;

enum SlaStatus: string
{
    case ONTIME = 'ontime';
    case LATE   = 'late';
    case RISK   = 'risk';

    public function label(): string
    {
        return match ($this) {
            self::ONTIME => 'SLA Tercapai',
            self::LATE   => 'SLA Tidak Tercapai',
            self::RISK   => 'Berisiko',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::ONTIME => 'bg-green-100 text-green-700',
            self::LATE   => 'bg-red-100 text-red-700',
            self::RISK   => 'bg-orange-100 text-orange-700',
        };
    }
}