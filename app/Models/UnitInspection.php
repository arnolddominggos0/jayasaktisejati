<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * UnitInspection
 *
 * Satu record = satu stage inspeksi untuk satu unit.
 * Stage: pickup → handover_depot → loading → unloading → selfdrive → dooring
 *
 * Source:
 *   live              = dilakukan real-time oleh petugas
 *   historical_import = di-generate retroaktif via UnitInspectionGenerator
 *
 * @property int         $id
 * @property int         $unit_id
 * @property string      $stage
 * @property string      $status           passed | failed
 * @property string      $source           live | historical_import
 * @property \Carbon\Carbon|null $checked_at
 * @property string|null $notes
 */
class UnitInspection extends Model
{
    use HasFactory;

    protected $table = 'unit_inspections';

    protected $fillable = [
        'unit_id',
        'stage',
        'status',
        'source',
        'checked_at',
        'notes',
    ];

    protected $casts = [
        'checked_at' => 'datetime',
    ];

    // ── Stage & Status constants ──────────────────────────────────────────────

    public const STAGES = [
        'pickup',
        'handover_depot',
        'loading',
        'unloading',
        'selfdrive',
        'dooring',
    ];

    public const STAGE_LABELS = [
        'pickup'          => 'Pickup (PDC Asal)',
        'handover_depot'  => 'Handover Depo',
        'loading'         => 'Loading (Stuffing)',
        'unloading'       => 'Unloading (Stripping)',
        'selfdrive'       => 'Selfdrive',
        'dooring'         => 'Dooring (PDC Tujuan)',
    ];

    public const STATUS_PASSED = 'passed';
    public const STATUS_FAILED = 'failed';

    public const SOURCE_LIVE              = 'live';
    public const SOURCE_HISTORICAL_IMPORT = 'historical_import';

    // ── Relations ─────────────────────────────────────────────────────────────

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(UnitInspectionItem::class);
    }

    // ── Accessors ─────────────────────────────────────────────────────────────

    /**
     * Label human-readable untuk stage ini.
     */
    public function getStageLabelAttribute(): string
    {
        return self::STAGE_LABELS[$this->stage] ?? ucfirst(str_replace('_', ' ', $this->stage));
    }

    /**
     * Apakah seluruh item ng-free?
     */
    public function getIsPassedAttribute(): bool
    {
        return $this->status === self::STATUS_PASSED;
    }

    /**
     * Hitung jumlah item NG dalam inspection ini.
     */
    public function getNgCountAttribute(): int
    {
        return $this->items()->where('result', 'ng')->count();
    }
}
