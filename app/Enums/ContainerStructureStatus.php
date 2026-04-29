<?php

namespace App\Enums;

enum ContainerStructureStatus: string
{
    case Good = 'good';
    case Damaged = 'damaged';
    case Leaking = 'leaking';

    public function label(): string
    {
        return match ($this) {
            self::Good => 'Baik',
            self::Damaged => 'Rusak',
            self::Leaking => 'Bocor',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Good => 'success',
            self::Damaged => 'warning',
            self::Leaking => 'danger',
        };
    }

    public function isCritical(): bool
    {
        return $this === self::Leaking;
    }
}
