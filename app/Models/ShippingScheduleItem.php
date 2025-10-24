<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ShippingScheduleItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'shipping_schedule_id',
        'etd',
        'eta',
        'cargo_plan',
        'vessel_name',
        'vessel_capacity',
        'voyage_no',
        'jss',
        'lts',
        'dwelling',
    ];

    protected $casts = [
        'etd' => 'datetime',
        'eta' => 'datetime',
    ];

    public function schedule()
    {
        return $this->belongsTo(ShippingSchedule::class, 'shipping_schedule_id');
    }
}
