<?php

namespace App\Enums;

enum MaintenanceReason: string {
    case Scheduled  = 'scheduled';
    case Oil        = 'oil';
    case Tire       = 'tire';
    case Brake      = 'brake';
    case Electrical = 'electrical';
    case Body       = 'body';
    case Inspection = 'inspection';
    case Breakdown  = 'breakdown';
    case Other      = 'other';

    public function label(): string {
        return match($this){
            self::Scheduled  => 'Perbaikan berkala',
            self::Oil        => 'Ganti oli',
            self::Tire       => 'Ban/Velg',
            self::Brake      => 'Rem',
            self::Electrical => 'Kelistrikan',
            self::Body       => 'Body/Cat',
            self::Inspection => 'Inspeksi',
            self::Breakdown  => 'Kerusakan di jalan',
            self::Other      => 'Lainnya',
        };
    }
}
