<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * UnitInspectionItem
 *
 * Satu baris item pemeriksaan dalam sebuah stage inspection.
 * Contoh: category="EXTERIOR", item_name="Lampu Depan", result="ok"
 *
 * Finding type (hanya relevan jika result = ng):
 *   major_damage     = kerusakan fisik → memicu RETURN_TO_PDC
 *   minor_missing    = item hilang/tidak ada → memicu ALLOW_WITH_REMARK
 *   information_only = catatan informasi, tidak mempengaruhi gate decision
 *
 * @property int         $id
 * @property int         $unit_inspection_id
 * @property string      $category
 * @property string      $item_name
 * @property string      $result       ok | ng
 * @property string|null $finding_type major_damage | minor_missing | information_only
 * @property string|null $notes
 * @property string|null $photo_url
 */
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
