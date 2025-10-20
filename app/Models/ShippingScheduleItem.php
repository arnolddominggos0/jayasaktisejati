<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
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
        'extra',
    ];

    protected $casts = [
        'etd' => 'datetime',
        'eta' => 'datetime',
        'extra' => 'array',
    ];

    protected $appends = [
        'cargo_plan',
        'vessel_capacity',
        'jss',
        'dwelling',
    ];

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(ShippingSchedule::class, 'schedule_id');
    }

    public function shippingLine(): BelongsTo
    {
        return $this->belongsTo(\App\Models\ShippingLine::class);
    }

    public function vessel(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Vessel::class);
    }

    public function pol(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Port::class, 'pol_id');
    }

    public function pod(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Port::class, 'pod_id');
    }

    protected function cargoPlan(): Attribute
    {
        return Attribute::get(fn() => $this->extra['cargo_plan'] ?? null);
    }

    protected function vesselCapacity(): Attribute
    {
        return Attribute::get(fn() => $this->extra['vessel_capacity'] ?? ($this->extra['capacity'] ?? null));
    }

    protected function jss(): Attribute
    {
        return Attribute::get(fn() => $this->extra['jss'] ?? null);
    }

    protected function dwelling(): Attribute
    {
        return Attribute::get(fn() => $this->extra['dwelling'] ?? null);
    }
}
