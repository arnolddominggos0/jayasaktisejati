<?php

namespace App\Enums;

enum MPRecheckResult: string
{
    case Fit = 'fit';
    case Unfit = 'unfit';

    public function label(): string
    {
        return match ($this) {
            self::Fit => 'Fit / Layak',
            self::Unfit => 'Tidak Layak',
        };
    }
}
