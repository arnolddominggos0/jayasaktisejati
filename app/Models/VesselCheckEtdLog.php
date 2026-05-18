<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VesselCheckEtdLog extends Model
{
    protected $fillable = [
        'vessel_check_case_id',
        'etd_before',
        'etd_after',
        'source',
        'note',
        'created_by',
    ];

    protected $casts = [
        'etd_before' => 'datetime',
        'etd_after'  => 'datetime',
    ];

    public function case(): BelongsTo
    {
        return $this->belongsTo(VesselCheckCase::class, 'vessel_check_case_id');
    }
}