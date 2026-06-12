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
 * Gate Decision (SOP TAM):
 *   accept            = semua item OK atau hanya information_only NG
 *   allow_with_remark = ada minor_missing NG, tidak ada major_damage NG
 *   return_to_pdc     = ada major_damage NG
 *
 * @property int              $id
 * @property int              $unit_id
 * @property string           $stage
 * @property string           $status           passed | failed
 * @property string           $source           live | historical_import
 * @property int|null         $checked_by
 * @property string|null      $gate_decision    accept | allow_with_remark | return_to_pdc
 * @property \Carbon\Carbon|null $submitted_at
 * @property \Carbon\Carbon|null $checked_at
 * @property string|null      $notes
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
        'checked_by',
        'gate_decision',
        'submitted_at',
        'checked_at',
        'notes',
    ];

    protected $casts = [
        'checked_at'   => 'datetime',
        'submitted_at' => 'datetime',
    ];

    // ── Stage constants ───────────────────────────────────────────────────────

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

    // ── Status constants ──────────────────────────────────────────────────────

    public const STATUS_PASSED = 'passed';
    public const STATUS_FAILED = 'failed';

    // ── Source constants ──────────────────────────────────────────────────────

    public const SOURCE_LIVE              = 'live';
    public const SOURCE_HISTORICAL_IMPORT = 'historical_import';

    // ── Gate decision constants ───────────────────────────────────────────────

    public const GATE_ACCEPT            = 'accept';
    public const GATE_ALLOW_WITH_REMARK = 'allow_with_remark';
    public const GATE_RETURN_TO_PDC     = 'return_to_pdc';

    public const GATE_LABELS = [
        'accept'            => 'Accept',
        'allow_with_remark' => 'Allow with Remark',
        'return_to_pdc'     => 'Return to PDC',
    ];

    // ── Relations ─────────────────────────────────────────────────────────────

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function checkedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(UnitInspectionItem::class);
    }

    // ── Accessors ─────────────────────────────────────────────────────────────

    public function getStageLabelAttribute(): string
    {
        return self::STAGE_LABELS[$this->stage] ?? ucfirst(str_replace('_', ' ', $this->stage));
    }

    public function getIsPassedAttribute(): bool
    {
        return $this->status === self::STATUS_PASSED;
    }

    public function getNgCountAttribute(): int
    {
        return $this->items()->where('result', 'ng')->count();
    }

    public function getIsSubmittedAttribute(): bool
    {
        return $this->submitted_at !== null;
    }
}
