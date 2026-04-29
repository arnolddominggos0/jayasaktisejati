<?php

namespace App\Enums;

// App\Enums\PpeCondition.php
enum PpeCondition: string
{
    case Baik = 'baik';
    case Rusak = 'rusak';
    case TidakAda = 'tidak_ada';

    public function label(): string
    {
        return match ($this) {
            self::Baik => 'Baik',
            self::Rusak => 'Rusak',
            self::TidakAda => 'Tidak Ada',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Baik => 'success',
            self::Rusak => 'danger',
            self::TidakAda => 'warning',
        };
    }
}

