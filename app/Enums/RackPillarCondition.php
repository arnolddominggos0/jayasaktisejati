<?php

namespace App\Enums;

enum RackPillarCondition: string
{
    case StrongAndStraight = 'strong_and_straight';
    case NotStraight = 'not_straight';
    case Damaged = 'damaged';

    public function label(): string
    {
        return match ($this) {
            self::StrongAndStraight => 'Kuat & Lurus',
            self::NotStraight => 'Tidak Lurus',
            self::Damaged => 'Rusak',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::StrongAndStraight => 'success',
            self::NotStraight => 'warning',
            self::Damaged => 'danger',
        };
    }

    public function isCritical(): bool
    {
        return $this === self::Damaged;
    }
}
