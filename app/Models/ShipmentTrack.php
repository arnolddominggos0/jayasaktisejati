<?php

namespace App\Models;

use App\Enums\TrackStatus;
use Illuminate\Database\Eloquent\Model;
use Filament\Facades\Filament;

class ShipmentTrack extends Model
{
    protected $table = 'shipment_tracks';

    protected $fillable = [
        'shipment_id',
        'status',
        'tracked_at',
        'location',
        'note',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'tracked_at' => 'datetime',
        'status'     => TrackStatus::class,
    ];

    public function shipment()
    {
        return $this->belongsTo(Shipment::class, 'shipment_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    protected static function booted(): void
    {
        static::creating(function (ShipmentTrack $track) {
            $uid = Filament::auth()?->id() ?: (auth()->check() ? auth()->id() : null);
            if ($uid) {
                $track->created_by ??= $uid;
                $track->updated_by ??= $uid;
            }
            $track->tracked_at ??= now();
        });

        static::updating(function (ShipmentTrack $track) {
            $uid = Filament::auth()?->id() ?: (auth()->check() ? auth()->id() : null);
            if ($uid) {
                $track->updated_by = $uid;
            }
        });
    }
}
