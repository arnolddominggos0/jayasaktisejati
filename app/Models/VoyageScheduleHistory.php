<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Snapshot jadwal ETD/ETA per voyage pada tiga titik:
 *
 *   'draft'  — jadwal dari draft_submitted snapshot
 *   'final'  — jadwal saat vessel plan difinalisasi (= voyage.etd/eta)
 *   'actual' — waktu keberangkatan/tiba aktual (= voyage.atd_at/ata_at)
 *
 * sailing_days disimpan agar variance bisa dihitung langsung tanpa
 * mengulang kalkulasi (terutama berguna ketika membandingkan 3 versi).
 *
 * captured_at / captured_by = waktu & aktor saat snapshot dibuat.
 */
class VoyageScheduleHistory extends Model
{
    protected $fillable = [
        'voyage_id',
        'schedule_type',   // draft | final | actual
        'etd',
        'eta',
        'sailing_days',
        'notes',
        'captured_at',
        'captured_by',
    ];

    protected $casts = [
        'etd'         => 'datetime',
        'eta'         => 'datetime',
        'sailing_days' => 'decimal:2',
        'captured_at' => 'datetime',
    ];

    // ──────────────────────────────────────────────────────────────────────
    // Relationships
    // ──────────────────────────────────────────────────────────────────────

    public function voyage(): BelongsTo
    {
        return $this->belongsTo(Voyage::class);
    }

    // ──────────────────────────────────────────────────────────────────────
    // Type helpers
    // ──────────────────────────────────────────────────────────────────────

    public function isDraft(): bool  { return $this->schedule_type === 'draft'; }
    public function isFinal(): bool  { return $this->schedule_type === 'final'; }
    public function isActual(): bool { return $this->schedule_type === 'actual'; }

    // ──────────────────────────────────────────────────────────────────────
    // Static helpers — variance antara dua history record
    // ──────────────────────────────────────────────────────────────────────

    /**
     * Selisih sailing_days antara dua record (bisa negatif).
     * Positif = lebih lama dari baseline, Negatif = lebih cepat.
     */
    public static function sailingVariance(?self $baseline, ?self $compare): ?float
    {
        if ($baseline?->sailing_days === null || $compare?->sailing_days === null) {
            return null;
        }

        return round((float) $compare->sailing_days - (float) $baseline->sailing_days, 2);
    }

    /**
     * Hitung sailing_days dari dua datetime (dalam hari, 2 desimal).
     */
    public static function calcSailingDays(mixed $from, mixed $to): ?float
    {
        if (! $from || ! $to) {
            return null;
        }

        $fromDt = $from instanceof \Carbon\Carbon ? $from : \Carbon\Carbon::parse($from);
        $toDt   = $to   instanceof \Carbon\Carbon ? $to   : \Carbon\Carbon::parse($to);

        return round($fromDt->diffInSeconds($toDt) / 86400, 2);
    }
}
