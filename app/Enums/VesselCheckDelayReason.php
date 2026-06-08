<?php

namespace App\Enums;

/**
 * Alasan keterlambatan khusus untuk Vessel Check (carrier readiness).
 *
 * Berbeda dari VoyageDelayReason yang digunakan untuk voyage-level delay analysis.
 * Enum ini mencakup alasan operasional H-2/H-1 yang relevan untuk konfirmasi
 * kesiapan carrier sebelum keberangkatan.
 */
enum VesselCheckDelayReason: string
{
    case QUEUE    = 'antrian_sandar';
    case WEATHER  = 'cuaca';
    case SCHEDULE = 'jadwal_pelayaran_berubah';
    case HOLIDAY  = 'hari_libur_nasional';
    case OTHER    = 'lainnya';

    public function label(): string
    {
        return match ($this) {
            self::QUEUE    => 'Antrian Sandar',
            self::WEATHER  => 'Cuaca',
            self::SCHEDULE => 'Jadwal Pelayaran Berubah',
            self::HOLIDAY  => 'Hari Libur Nasional',
            self::OTHER    => 'Lainnya',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn($c) => [$c->value => $c->label()])
            ->toArray();
    }
}
