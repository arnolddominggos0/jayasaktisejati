<?php

namespace App\Services;

use Illuminate\Support\Carbon;

/**
 * Helper untuk menghitung durasi operasional pelayaran dalam satuan hari.
 *
 * Aturan:
 * - Semua durasi dihitung dalam hari (bukan jam/menit).
 * - Minimum 1 hari bila ada aktivitas (ceil).
 * - Delay ≤ 0 hari = On Time.
 * - Timestamp asli tetap disimpan di database; layer ini hanya untuk presentasi & KPI.
 */
class OperationalDaysHelper
{
    /**
     * Hitung selisih hari antara dua timestamp.
     * Hasil selalu integer ≥ 0.
     */
    public static function diffInDays(?Carbon $from, ?Carbon $to): ?int
    {
        if (! $from || ! $to) {
            return null;
        }

        $hours = $from->diffInHours($to, false);

        if ($hours <= 0) {
            return 0;
        }

        return (int) ceil($hours / 24);
    }

    /**
     * Hitung delay dalam hari (actual – planned).
     * Hasil 0 berarti on-time atau early.
     */
    public static function delayDays(?Carbon $planned, ?Carbon $actual): ?int
    {
        if (! $planned || ! $actual) {
            return null;
        }

        $hours = $planned->diffInHours($actual, false);

        if ($hours <= 0) {
            return 0;
        }

        return (int) ceil($hours / 24);
    }

    /**
     * Kategori severity berbasis hari.
     *
     * 0 hari   = on time (null)
     * 1–2 hari = ringan
     * 3–5 hari = sedang
     * >5 hari  = berat
     */
    public static function severity(?int $days): ?string
    {
        if ($days === null || $days <= 0) {
            return null;
        }

        if ($days <= 2) {
            return 'minor';
        }

        if ($days <= 5) {
            return 'moderate';
        }

        return 'major';
    }

    /**
     * Label delay untuk tampilan UI.
     * Contoh: "Terlambat 3 Hari"
     */
    public static function delayLabel(?int $days): ?string
    {
        if ($days === null || $days <= 0) {
            return null;
        }

        return 'Terlambat ' . $days . ' Hari';
    }

    /**
     * Format durasi sailing/elapsed untuk UI.
     * Contoh: "5 Hari"
     */
    public static function durationLabel(?int $days): ?string
    {
        if ($days === null || $days <= 0) {
            return null;
        }

        return $days . ' Hari';
    }
}
