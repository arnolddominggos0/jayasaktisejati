<?php

namespace App\Enums;

enum VesselPlanStatus: string
{
    case Draft    = 'draft';
    case Sent     = 'sent';
    case Revision = 'revision';
    case Final    = 'final';

    public function label(): string
    {
        return match ($this) {
            self::Draft    => 'Draft',
            self::Sent     => 'Terkirim',
            self::Revision => 'Perlu Revisi',
            self::Final    => 'Final',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft    => 'gray',
            self::Sent     => 'warning',
            self::Revision => 'danger',
            self::Final    => 'success',
        };
    }
}