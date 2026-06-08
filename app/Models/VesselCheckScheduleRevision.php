<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** @deprecated See VesselCheckCase @deprecated */
class VesselCheckScheduleRevision extends Model
{
    protected $fillable = [
        'vessel_check_case_id',
        'old_voyage_id',
        'new_voyage_id',
        'old_etd',
        'new_etd',
        'revision_note',
    ];

    protected $casts = [
        'old_etd' => 'datetime',
        'new_etd' => 'datetime',
    ];

    public function case(): BelongsTo
    {
        return $this->belongsTo(VesselCheckCase::class, 'vessel_check_case_id');
    }

    public function oldVoyage(): BelongsTo
    {
        return $this->belongsTo(Voyage::class, 'old_voyage_id');
    }

    public function newVoyage(): BelongsTo
    {
        return $this->belongsTo(Voyage::class, 'new_voyage_id');
    }
}
