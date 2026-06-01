<?php

namespace App\Enums;

enum SeaBookingStatus: string
{
    case Draft        = 'draft';        // baru terima RO/RC dari customer
    case Requested    = 'requested';    // sudah diajukan ke pelayaran
    case Confirmed    = 'confirmed';    // sudah dikonfirmasi oleh pelayaran
    case InProgress   = 'in_progress';  // proses jalan (ambil kontainer / gate-in / stuffing)
    case Completed    = 'completed';    // kapal berangkat / kontainer sudah tiba
    case Cancelled    = 'cancelled';    // booking dibatalkan

    public function label(): string
    {
        return match ($this) {
            self::Draft      => 'Draf Permintaan',
            self::Requested  => 'Diajukan ke Pelayaran',
            self::Confirmed  => 'Dikonfirmasi Pelayaran',
            self::InProgress => 'Dalam Proses Pengiriman',
            self::Completed  => 'Selesai (Kapal Berangkat)',
            self::Cancelled  => 'Dibatalkan',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft      => 'gray',
            self::Requested  => 'warning',
            self::Confirmed  => 'info',
            self::InProgress => 'primary',
            self::Completed  => 'success',
            self::Cancelled  => 'danger',
        };
    }
}
