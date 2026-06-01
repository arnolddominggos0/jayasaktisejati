<?php

namespace App\Enums;

enum ContainerSize: string
{
    case COC_20_DRY    = 'coc_20_dry';
    case COC_40_DRY_HC = 'coc_40_dry_hc';
    case COC_40_DRY    = 'coc_40_dry';
    case COC_21_DRY    = 'coc_21_dry';

    public function label(): string
    {
        return match ($this) {
            self::COC_20_DRY    => 'COC 20 DRY',
            self::COC_40_DRY_HC => 'COC 40 DRY HC',
            self::COC_40_DRY    => 'COC 40 DRY',
            self::COC_21_DRY    => 'COC 21 DRY',
        };
    }

    public static function options(): array
    {
        return [
            self::COC_20_DRY->value    => self::COC_20_DRY->label(),
            self::COC_40_DRY_HC->value => self::COC_40_DRY_HC->label(),
            self::COC_40_DRY->value    => self::COC_40_DRY->label(),
            self::COC_21_DRY->value    => self::COC_21_DRY->label(),
        ];
    }

    public static function tryLabel(?string $value): ?string
    {
        return self::tryFrom((string)$value)?->label();
    }
}
