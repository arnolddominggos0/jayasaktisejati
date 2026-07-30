<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UnitInspectionItem extends Model
{
    use HasFactory;

    protected $table = 'unit_inspection_items';

    protected $fillable = [
        'unit_inspection_id',
        'category',
        'item_name',
        'result',
        'finding_type',
        'notes',
        'photo_url',
    ];

    // ── Result constants ──────────────────────────────────────────────────────

    public const RESULT_OK = 'ok';
    public const RESULT_NG = 'ng';

    // ── Finding type constants ────────────────────────────────────────────────

    public const FINDING_MAJOR_DAMAGE      = 'major_damage';
    public const FINDING_MINOR_MISSING     = 'minor_missing';
    public const FINDING_INFORMATION_ONLY  = 'information_only';

    public const FINDING_LABELS = [
        'major_damage'     => 'Kerusakan Fisik',
        'minor_missing'    => 'Item Hilang / Tidak Ada',
        'information_only' => 'Informasi',
    ];

    // ── Relations ─────────────────────────────────────────────────────────────

    public function inspection(): BelongsTo
    {
        return $this->belongsTo(UnitInspection::class, 'unit_inspection_id');
    }

    // ── Accessors ─────────────────────────────────────────────────────────────

    public function getIsOkAttribute(): bool
    {
        return $this->result === self::RESULT_OK;
    }

    public function getIsNgAttribute(): bool
    {
        return $this->result === self::RESULT_NG;
    }

    public function getIsMajorDamageAttribute(): bool
    {
        return $this->result === self::RESULT_NG
            && $this->finding_type === self::FINDING_MAJOR_DAMAGE;
    }
}
