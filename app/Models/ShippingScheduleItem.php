<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
<<<<<<< HEAD
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
=======
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;

class ShippingScheduleItem extends Model
{
    protected $fillable = [
        'schedule_id',
        'shipping_line_id',
        'vessel_id',
        'voyage_no',
        'pol_id',
        'pod_id',
        'etd',
        'eta',
        'service',
        'jss',
        'extra',
        'voyage_id',
    ];

    protected $casts = [
        'etd'   => 'datetime',
        'eta'   => 'datetime',
        'extra' => 'array',
    ];

    protected $appends = [
        'cargo_plan',
        'vessel_capacity',
        'dwelling',
        'voyage',
    ];

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(ShippingSchedule::class, 'schedule_id');
    }

    public function shippingLine(): BelongsTo
    {
        return $this->belongsTo(ShippingLine::class);
    }

    public function vessel(): BelongsTo
    {
        return $this->belongsTo(Vessel::class);
    }

    public function pol(): BelongsTo
    {
        return $this->belongsTo(Port::class, 'pol_id');
    }

    public function pod(): BelongsTo
    {
        return $this->belongsTo(Port::class, 'pod_id');
    }

    protected function cargoPlan(): Attribute
    {
        return Attribute::get(fn() => $this->extra['cargo_plan'] ?? null);
    }

    protected function vesselCapacity(): Attribute
    {
        return Attribute::get(fn() => $this->extra['vessel_capacity'] ?? ($this->extra['capacity'] ?? null));
    }

    protected function dwelling(): Attribute
    {
        return Attribute::get(fn() => $this->extra['dwelling'] ?? null);
    }

    protected function voyage(): Attribute
    {
        return Attribute::get(fn() => $this->voyage_no ?? ($this->extra['voyage_no'] ?? null));
>>>>>>> 1dcaff98d6e0ae89c5b689574805eed309eb1f47
    }
}
