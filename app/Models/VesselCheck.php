<?php

namespace App\Models;

use App\Enums\VesselCheckLogStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class VesselCheck extends Model
{
    protected $fillable = [
        'voyage_id',
        'check_date',
        'day_code',
        'status',
        'delay_reason',
        'note',
    ];

    protected $casts = [
        'check_date' => 'date',
        'status'     => VesselCheckLogStatus::class,
    ];

    public function voyage(): BelongsTo
    {
        return $this->belongsTo(Voyage::class);
    }

    public function vesselCheckCase(): HasOne
    {
        return $this->hasOne(VesselCheckCase::class, 'voyage_id', 'voyage_id');
    }
}
