<?php

namespace App\Enums;

enum MPBackupType: string
{
    case Internal = 'internal';
    case External = 'external';

    public function label(): string
    {
        return match ($this) {
            self::Internal => 'Internal',
            self::External => 'Eksternal',
        };
    }
}
