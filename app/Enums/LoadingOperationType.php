<?php

namespace App\Enums;

enum LoadingOperationType: string
{
    case Loading = 'loading';
    case Unloading = 'unloading';
    case RackHandling = 'rack_handling';

    public function label(): string
    {
        return match ($this) {
            self::Loading => 'Loading',
            self::Unloading => 'Unloading',
            self::RackHandling => 'Rack Handling',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Loading => 'success',
            self::Unloading => 'warning',
            self::RackHandling => 'info',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Loading => 'heroicon-o-arrow-up-tray',
            self::Unloading => 'heroicon-o-arrow-down-tray',
            self::RackHandling => 'heroicon-o-cube',
        };
    }
}
