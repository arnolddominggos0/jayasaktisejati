<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VesselPlanSnapshot extends Model
{
    protected $fillable = [
        'vessel_plan_id',
        'stage',
        'schedule_payload',
        'kpi_payload',
        'created_by',
    ];

    protected $casts = [
        'schedule_payload' => 'array',
        'kpi_payload' => 'array',
    ];

    public function vesselPlan(): BelongsTo
    {
        return $this->belongsTo(VesselPlan::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
