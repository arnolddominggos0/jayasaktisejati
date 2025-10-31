<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Voyage extends Model
{
    protected $fillable = ['vessel_id', 'pol_id', 'pod_id', 'voyage_no', 'service', 'etd', 'eta'];
    protected $casts = ['etd' => 'datetime', 'eta' => 'datetime'];

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

    public function schedule(): HasOne
    {
        return $this->hasOne(ShippingSchedule::class);
    }
}
