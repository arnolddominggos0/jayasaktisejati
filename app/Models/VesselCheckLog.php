<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VesselCheckLog extends Model
{
    protected $fillable = [
        'vessel_check_case_id',
        'check_date',
        'day_code',
        'etd_plan',
        'etd_current',
        'status',
        'source',
    ];

    protected $casts = [
        'check_date' => 'date',
        'etd_plan' => 'datetime',
        'etd_current' => 'datetime',
    ];

    public function case(): BelongsTo
    {
        return $this->belongsTo(VesselCheckCase::class, 'vessel_check_case_id');
    }
}
