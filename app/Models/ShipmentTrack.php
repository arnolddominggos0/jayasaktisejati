<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShipmentTrack extends Model
{
    protected $fillable = [
        'shipment_id',
        'tracked_at',
        'status',
        'location',
        'checkpoint',
        'note',
        'meta',
        'user_id'
    ];
    protected $casts = [
        'tracked_at' => 'datetime',
        'meta' => 'array'
    ];

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tracks()
    {
        return $this->hasMany(ShipmentTrack::class)->orderByDesc('tracked_at');
    }
    public function latestTrack()
    {
        return $this->hasOne(ShipmentTrack::class)->latestOfMany('tracked_at');
    }
}
