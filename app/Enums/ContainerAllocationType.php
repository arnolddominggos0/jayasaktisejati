<?php

namespace App\Enums;

enum ContainerAllocationType: string
{
    case Rack = 'rack';
    case Regular = 'regular';

    public function label(): string
    {
        return match ($this) {
            self::Rack => 'Rack',
            self::Regular => 'Regular',
        };
    }
}
