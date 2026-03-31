<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VoyageCheckpoint extends Model
{
    protected $fillable = [
        'voyage_id',
        'code',
        'offset_days',
        'scheduled_at',
        'checked_at',
        'status',
        'note',
        'checked_by',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'checked_at'   => 'datetime',
    ];

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
