<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VesselCheckDelay extends Model
{
    protected $fillable = [
        'vessel_check_case_id',
        'delay_category',
        'delay_reason',
        'delay_minutes',
        'impact_description',
        'analysis_date',
    ];

    protected $casts = [
        'analysis_date' => 'date',
        'delay_minutes' => 'integer',
    ];

    public function case(): BelongsTo
    {
        return $this->belongsTo(VesselCheckCase::class, 'vessel_check_case_id');
    }
}
