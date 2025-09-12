<?php

namespace App\Enums;

enum ContainerSize: string
{
    case FT20   = '20';
    case FT40   = '40';
    case FT40HC = '40HC';
    case FT45HC = '45HC';

    public function label(): string
    {
        return match ($this) {
            self::FT20   => "20'",
            self::FT40   => "40'",
            self::FT40HC => "40' HC",
            self::FT45HC => "45' HC",
        };
    }

    /** Helper untuk option Filament */
    public static function options(): array
    {
        return [
            self::FT20->value   => self::FT20->label(),
            self::FT40->value   => self::FT40->label(),
            self::FT40HC->value => self::FT40HC->label(),
            self::FT45HC->value => self::FT45HC->label(),
        ];
    }
}
