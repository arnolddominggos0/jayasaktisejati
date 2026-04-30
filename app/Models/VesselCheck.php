<?php

namespace App\Models;

use App\Enums\VesselCheckLogStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VesselCheck extends Model
{
    protected $fillable = [
        'vessel_check_id',
        'shipping_schedule_id',
        'check_date',
        'day_code',
        'etd_plan',
        'etd_current',
        'status',
        'note',
        'source',
    ];

    protected $casts = [
        'check_date'  => 'date',
        'etd_plan'    => 'datetime',
        'etd_current' => 'datetime',
        'status'      => VesselCheckLogStatus::class,
    ];  

    public function shippingSchedule(): BelongsTo
    {
        return $this->belongsTo(ShippingSchedule::class);
    }

    public function voyage(): BelongsTo
    {
        return $this->belongsTo(Voyage::class);
    }
}   
