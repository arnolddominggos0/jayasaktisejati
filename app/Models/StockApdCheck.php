<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockApdCheck extends Model
{
    protected $fillable = [
        'session_id',
        'ppe_type',
        'stock_available',
        'required_quantity',
        'status',
        'remark',
    ];

    protected $casts = [
        'stock_available'  => 'integer',
        'required_quantity' => 'integer',
    ];

    /**
     * Include computed attributes in array/JSON serialization
     * so Filament Infolist / RelationManager table can read them.
     */
    protected $appends = ['gap', 'computed_status'];

    // ─── Accessors ──────────────────────────────────────────────────────────

    /**
     * Gap = stock_available − required_quantity.
     *   Positive → surplus, Negative → shortage, null → data not yet filled.
     */
    public function getGapAttribute(): ?int
    {
        if ($this->stock_available === null || $this->required_quantity === null) {
            return null;
        }

        return $this->stock_available - $this->required_quantity;
    }

    /**
     * Computed status based on actual stock vs requirement.
     * ALWAYS use this instead of the raw `status` DB column to avoid
     * the AppSheet inconsistency bug (status='cukup' but stock < required).
     *
     * Returns: 'cukup' | 'kurang' | 'belum_diisi'
     */
    public function getComputedStatusAttribute(): string
    {
        if ($this->stock_available === null || $this->required_quantity === null) {
            return 'belum_diisi';
        }

        return $this->stock_available >= $this->required_quantity ? 'cukup' : 'kurang';
    }

    // ─── Relations ──────────────────────────────────────────────────────────

    public function session(): BelongsTo
    {
        return $this->belongsTo(BriefingSession::class, 'session_id');
    }
}
