<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShippingLine extends Model
{
    protected $fillable = ['code', 'name'];

    public function vessels(): HasMany
    {
        return $this->hasMany(Vessel::class);
    }
    protected static function booted()
    {
        static::creating(function ($vessel) {
            $vessel->loadMissing('shippingLine');
            $vessel->code = $vessel->code ?? VesselCode::for($vessel);
        });
    }
}
