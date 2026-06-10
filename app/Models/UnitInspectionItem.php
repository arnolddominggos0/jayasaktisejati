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
 * @property int         $id
 * @property int         $unit_inspection_id
 * @property string      $category
 * @property string      $item_name
 * @property string      $result       ok | ng
 * @property string|null $notes
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
        'notes',
    ];

    public const RESULT_OK = 'ok';
    public const RESULT_NG = 'ng';

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
}
