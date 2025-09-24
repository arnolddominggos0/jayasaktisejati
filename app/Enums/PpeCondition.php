<?php

namespace App\Enums;

enum PpeCondition: string
{
    case Baik  = 'baik';
    case Rusak = 'rusak';

    public function label(): string
    {
        return match ($this) {
            self::Baik  => 'Baik',
            self::Rusak => 'Rusak',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Baik  => 'success',
            self::Rusak => 'danger',
        };
    }
}
