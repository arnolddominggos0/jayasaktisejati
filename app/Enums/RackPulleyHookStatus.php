<?php

namespace App\Enums;

enum RackPulleyHookStatus: string
{
    case PresentAndStrong = 'present_and_strong';
    case NotPresent = 'not_present';
    case Loose = 'loose';
    case Damaged = 'damaged';

    public function label(): string
    {
        return match ($this) {
            self::PresentAndStrong => 'Ada & Kuat',
            self::NotPresent => 'Tidak Ada',
            self::Loose => 'Longgar',
            self::Damaged => 'Rusak',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PresentAndStrong => 'success',
            self::NotPresent,
            self::Loose,
            self::Damaged => 'danger',
        };
    }

    public function isCritical(): bool
    {
        return in_array($this, [self::NotPresent, self::Loose, self::Damaged], true);
    }
}
