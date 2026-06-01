<?php

namespace App\Enums;

enum PpeType: string
{
    case Helm         = 'helm';
    case SarungTangan = 'sarung_tangan';
    case Sepatu       = 'sepatu';
    case Rompi        = 'rompi';

    public function label(): string
    {
        return match ($this) {
            self::Helm         => 'Helm',
            self::SarungTangan => 'Sarung Tangan',
            self::Sepatu       => 'Sepatu',
            self::Rompi        => 'Rompi'
        };
    }
}
