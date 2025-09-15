<?php

namespace App\Enums;

enum SeaBookingStatus: string
{
    case Draft        = 'draft';        // baru terima RO/RC
    case Requested    = 'requested';    // request ke carrier
    case Confirmed    = 'confirmed';    // confirmed SLI/booking
    case InProgress   = 'in_progress';  // container jalan (ambil/gate-in/loading)
    case Completed    = 'completed';    // kapal berangkat / sampai tujuan
    case Cancelled    = 'cancelled';

    public function label(): string
    {
        return match($this) {
            self::Draft      => 'Draft',
            self::Requested  => 'Requested',
            self::Confirmed  => 'Confirmed',
            self::InProgress => 'In Progress',
            self::Completed  => 'Selesai',
            self::Cancelled  => 'Batal',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Draft      => 'gray',
            self::Requested  => 'warning',
            self::Confirmed  => 'info',
            self::InProgress => 'primary',
            self::Completed  => 'success',
            self::Cancelled  => 'danger',
        };
    }
}
