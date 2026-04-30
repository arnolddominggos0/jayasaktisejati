<?php

namespace App\Enums;

enum DropFloorStrength: string
{
    case Strong = 'strong';
    case Weak = 'weak';

    public function label(): string
    {
        return match ($this) {
            self::Strong => 'Kuat',
            self::Weak => 'Lemah',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Strong => 'success',
            self::Weak => 'danger',
        };
    }

    public function isCritical(): bool
    {
        return $this === self::Weak;
    }
}
