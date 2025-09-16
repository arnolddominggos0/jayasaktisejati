<?php


namespace App\Models;


use App\Enums\TrackStatus;
use Illuminate\Database\Eloquent\Model;


class ShipmentTrack extends Model
{
    protected $fillable = [
        'shipment_id',
        'status',
        'tracked_at',
        'location',
        'note',
        'created_by'
    ];


    protected $casts = [
        'tracked_at' => 'datetime',
        'status' => TrackStatus::class,
    ];

    protected $table = 'shipment_tracks';


    public function shipment()
    {
        return $this->belongsTo(Shipment::class, 'shipment_id', );
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function tracks()
    {
        return $this->hasMany(ShipmentTrack::class);
    }

    public function latestTrack()
    {
        return $this->hasOne(ShipmentTrack::class)->latestOfMany();
    }
}
