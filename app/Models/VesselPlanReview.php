<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VesselPlanReview extends Model
{
    protected $fillable = [
        'vessel_plan_id',
        'action',
        'note',
        'acted_by',
        'acted_at',
        'meta',
    ];

    protected $casts = [
        'acted_at' => 'datetime',
        'meta' => 'array',
    ];

    public function vesselPlan(): BelongsTo
    {
        return $this->belongsTo(VesselPlan::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acted_by');
    }
}
