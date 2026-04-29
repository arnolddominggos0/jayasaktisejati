<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class ShipSchedule extends Model
{
    protected $fillable = [
        'armada_id',
        'departure_time',
        'arrival_time',
        'origin_port',
        'destination_port',
        'voyage_number'
    ];

    public function armada(): BelongsTo
    {
        return $this->belongsTo(Armada::class);
    }
}
