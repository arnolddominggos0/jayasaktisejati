<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class City extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'province', 'country', 'slug', 'is_active'];

    public function originShipments() { return $this->hasMany(Shipment::class, 'origin_city_id'); }
    public function destinationShipments() { return $this->hasMany(Shipment::class, 'destination_city_id'); }
}
