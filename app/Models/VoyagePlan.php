<?php

namespace App\Models;

use App\Enums\VoyagePlanState;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VoyagePlan extends Model
{
    protected $fillable = [
        'voyage_id',
        'state',
        'payload',
        'notes',
        'source',
        'finalized_at',
        'created_by',
        'approval_ref',
    ];

    protected $casts = [
        'payload' => 'array',
        'finalized_at' => 'datetime',
    ];

    public function voyage(): BelongsTo
    {
        return $this->belongsTo(Voyage::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isFinal(): bool
    {
        return $this->state === VoyagePlanState::Final->value;
    }
}
