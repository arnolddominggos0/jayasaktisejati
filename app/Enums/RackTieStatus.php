<?php

namespace App\Enums;

enum RackTieStatus: string
{
    case TiedStrong = 'tied_strong';
    case NotTied = 'not_tied';
    case Loose = 'loose';

    public function label(): string
    {
        return match ($this) {
            self::TiedStrong => 'Terpasang Kuat',
            self::NotTied => 'Tidak Terpasang',
            self::Loose => 'Longgar',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::TiedStrong => 'success',
            self::NotTied,
            self::Loose => 'danger',
        };
    }

    public function isCritical(): bool
    {
        return in_array($this, [self::NotTied, self::Loose], true);
    }
}
