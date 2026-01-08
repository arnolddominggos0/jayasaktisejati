<?php

namespace App\Enums;

enum VesselPlanStatus: string
{
    case Draft = 'draft';
    case Sent  = 'sent';
    case Final = 'final';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Sent  => 'Terkirim ke TAM',
            self::Final => 'Final',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'warning',
            self::Sent  => 'primary',
            self::Final => 'success',
        };
    }
}
