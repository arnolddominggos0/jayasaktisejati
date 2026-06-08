<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'province', 'country', 'slug', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function originShipments()
    {
        return $this->hasMany(Shipment::class, 'origin_city_id');
    }

    public function destinationShipments()
    {
        return $this->hasMany(Shipment::class, 'destination_city_id');
    }

    public function customers()
    {
        return $this->hasMany(Customer::class, 'city_id');
    }

    public function hasActiveShipments(): bool
    {
        return $this->originShipments()->exists() || $this->destinationShipments()->exists();
    }
}
