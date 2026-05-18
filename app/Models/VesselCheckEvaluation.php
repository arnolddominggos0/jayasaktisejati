<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VesselCheckEvaluation extends Model
{
    protected $fillable = [
        'vessel_check_case_id',
        'final_status',
        'total_delay_minutes',
        'resolution_summary',
        'evaluated_by',
        'evaluated_at',
    ];

    protected $casts = [
        'total_delay_minutes' => 'integer',
        'evaluated_at'        => 'datetime',
    ];

    public function case(): BelongsTo
    {
        return $this->belongsTo(VesselCheckCase::class, 'vessel_check_case_id');
    }
}