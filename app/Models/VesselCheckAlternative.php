<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VesselCheckAlternative extends Model
{
    protected $fillable = [
        'vessel_check_case_id',
        'alt_vessel_id',
        'alt_voyage_id',
        'alt_etd',
        'approval_status',
        'proposal_note',
        'approved_at',
    ];

    protected $casts = [
        'alt_etd' => 'datetime',
        'approved_at' => 'datetime',
    ];

    public function case(): BelongsTo
    {
        return $this->belongsTo(VesselCheckCase::class, 'vessel_check_case_id');
    }

    public function vessel(): BelongsTo
    {
        return $this->belongsTo(Vessel::class, 'alt_vessel_id');
    }

    public function voyage(): BelongsTo
    {
        return $this->belongsTo(Voyage::class, 'alt_voyage_id');
    }
}
