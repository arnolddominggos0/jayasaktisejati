<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TamShipment extends Model
{
    protected $fillable = [
        'shipping_schedule_id',
        'vin',
        'engine_no',
        'model',                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                              
        'color',
        'do_number',
        'status',
        'gate_in_at',
        'loaded_at',
        'arrived_at',
        'delivered_at',
        'dwelling_days',
        'sailing_days',
        'dooring_days',
    ];

    protected $casts = [
        'gate_in_at'   => 'datetime',
        'loaded_at'    => 'datetime',
        'arrived_at'   => 'datetime',
        'delivered_at' => 'datetime',

        'dwelling_days' => 'integer',
        'sailing_days'  => 'integer',
        'dooring_days'  => 'integer',
    ];

    public function shippingSchedule(): BelongsTo
    {
        return $this->belongsTo(ShippingSchedule::class);
    }
}
