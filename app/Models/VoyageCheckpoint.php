<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VoyageCheckpoint extends Model
{
    protected $fillable = [
        'voyage_id',
        'code',
        'name',
        'offset_days',
        'scheduled_at',
        'completed_at',
        'status',
        'note',
        'checked_by',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'checked_at'   => 'datetime',
    ];

    // ── Canonical field mapping (legacy: title → name, checked_at → completed_at) ──

    public function setNameAttribute(?string $value): void
    {
        $this->attributes['title'] = $value;
    }

    public function getNameAttribute(): ?string
    {
        return $this->attributes['title'] ?? null;
    }

    public function setCompletedAtAttribute(?\Illuminate\Support\Carbon $value): void
    {
        $this->attributes['checked_at'] = $value;
    }

    public function getCompletedAtAttribute(): ?\Illuminate\Support\Carbon
    {
        return $this->getAttributeValue('checked_at');
    }

    public function voyage(): BelongsTo
    {
        return $this->belongsTo(Voyage::class);
    }

    public function getIsLateAttribute(): bool
    {
        if (!$this->scheduled_at || !$this->checked_at) {
            return false;
        }

        return $this->checked_at->gt($this->scheduled_at);
    }

    public function getIsCompletedAttribute(): bool
    {
        return !is_null($this->checked_at);
    }
}
