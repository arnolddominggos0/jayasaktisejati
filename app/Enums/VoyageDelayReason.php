<?php

namespace App\Enums;

enum VoyageDelayReason: string
{
    case WEATHER       = 'cuaca';
    case VESSEL        = 'kapal';
    case PORT          = 'pelabuhan';
    case OPERATIONAL   = 'operasional';
    case DOCUMENT      = 'dokumen';
    case OTHER         = 'lainnya';

    public function label(): string
    {
        return match ($this) {
            self::WEATHER     => 'Cuaca',
            self::VESSEL      => 'Masalah Kapal',
            self::PORT        => 'Kepadatan Pelabuhan',
            self::OPERATIONAL => 'Operasional',
            self::DOCUMENT    => 'Dokumen',
            self::OTHER       => 'Lainnya',
        };
    }
}
