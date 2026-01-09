<?php

namespace App\Models;

use App\Enums\VesselCheckStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VesselCheck extends Model
{
    protected $fillable = [
        'shipping_schedule_id',
        'check_date',
        'day_code',
        'etd_plan',
        'etd_current',
        'status',
        'delay_reason',
        'note',
        'source',
        'created_by',
    ];

    protected $casts = [
        'check_date' => 'date',
        'etd_plan' => 'datetime',
        'etd_current' => 'datetime',
        'status' => VesselCheckStatus::class,
    ];

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(ShippingSchedule::class);
    }

    public function voyage()
    {
        return $this->belongsTo(Voyage::class);
    }
}
