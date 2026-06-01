<?php

namespace App\Enums;

enum PpeCleanlinessStatus: string
{
    case Clean = 'clean';
    case Dirty = 'dirty';

    public function label(): string
    {
        return match ($this) {
            self::Clean => 'Bersih',
            self::Dirty => 'Kotor',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Clean => 'success',
            self::Dirty => 'warning',
        };
    }
}
