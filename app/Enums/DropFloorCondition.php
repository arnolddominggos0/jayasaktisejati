<?php

namespace App\Enums;

enum DropFloorCondition: string
{
    case Straight = 'straight';
    case Bent = 'bent';

    public function label(): string
    {
        return match ($this) {
            self::Straight => 'Lurus',
            self::Bent => 'Bengkok',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Straight => 'success',
            self::Bent => 'danger',
        };
    }

    public function isCritical(): bool
    {
        return $this === self::Bent;
    }
}
