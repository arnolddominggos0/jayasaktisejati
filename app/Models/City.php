<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    protected $fillable = ['name', 'province', 'country', 'slug', 'is_active'];

    public function originShipments() { return $this->hasMany(Shipment::class, 'origin_city_id'); }
    public function destinationShipments() { return $this->hasMany(Shipment::class, 'destination_city_id'); }
}
