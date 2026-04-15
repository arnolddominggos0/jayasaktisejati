<?php

namespace App\Enums;

enum IronHookStatus: string
{
    case Present = 'present';
    case NotPresent = 'not_present';
    case Damaged = 'damaged';

    public function label(): string
    {
        return match ($this) {
            self::Present => 'Ada',
            self::NotPresent => 'Tidak Ada',
            self::Damaged => 'Rusak',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Present => 'success',
            self::NotPresent,
            self::Damaged => 'danger',
        };
    }

    public function isCritical(): bool
    {
        return in_array($this, [self::NotPresent, self::Damaged], true);
    }
}
