<?php

namespace App\Enums;

enum ChecklistStatus: string
{
    case Ok      = 'ok';
    case Warning = 'warning';
    case Fail    = 'fail';

    public function label(): string
    {
        return match ($this) {
            self::Ok      => 'OK',
            self::Warning => 'Perlu Perhatian',
            self::Fail    => 'Gagal',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Ok      => 'success',
            self::Warning => 'warning',
            self::Fail    => 'danger',
        };
    }
}
