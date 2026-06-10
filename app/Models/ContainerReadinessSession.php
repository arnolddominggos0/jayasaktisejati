<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Container Readiness Session — demand planning harian.
 *
 * Bukan container tracking, bukan shipment execution.
 * Satu baris per hari (unique session_date).
 *
 * @property int     $id
 * @property \Illuminate\Support\Carbon  $session_date
 * @property int     $unit_count          Jumlah unit / SPPB hari ini
 * @property int     $container_need      Kebutuhan container
 * @property int     $container_available Container tersedia
 * @property int     $gap                 Stored: available - need
 * @property bool    $summary_sufficient  Stored: gap >= 0
 * @property string|null $notes
 */
class ContainerReadinessSession extends Model
{
    protected $table = 'container_readiness_sessions';

    protected $fillable = [
        'session_date',
        'unit_count',
        'container_need',
        'container_available',
        'gap',
        'summary_sufficient',
        'notes',
    ];

    protected $casts = [
        'session_date'        => 'date',
        'unit_count'          => 'integer',
        'container_need'      => 'integer',
        'container_available' => 'integer',
        'gap'                 => 'integer',
        'summary_sufficient'  => 'boolean',
    ];

    // ── Boot — auto-compute gap & summary_sufficient sebelum save ─────────────

    protected static function booted(): void
    {
        static::saving(function (self $model) {
            $model->gap                = $model->container_available - $model->container_need;
            $model->summary_sufficient = $model->container_available >= $model->container_need;
        });
    }

    // ── Accessors ─────────────────────────────────────────────────────────────

    /**
     * Gap = available - need.
     * Redundan dengan stored column, tapi disediakan agar konsisten dengan pola MP.
     */
    public function getGapAttribute(): int
    {
        // Jika sudah di-load dari DB, pakai stored value
        if (array_key_exists('gap', $this->attributes)) {
            return (int) $this->attributes['gap'];
        }

        return $this->container_available - $this->container_need;
    }

    /**
     * Status label — "READY" atau "NOT READY".
     * Mirror dari MP Readiness: OK / NG.
     */
    public function getStatusAttribute(): string
    {
        return $this->summary_sufficient ? 'READY' : 'NOT READY';
    }
}
