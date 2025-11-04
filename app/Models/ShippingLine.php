<?php

namespace App\Models;

use App\Support\VesselCode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShippingLine extends Model
{
    protected $fillable = ['code', 'name'];

    public function vessels(): HasMany
    {
        return $this->hasMany(Vessel::class);
    }
    public function schedules(): HasMany
    {
        return $this->hasMany(ShippingSchedule::class);
    }
}
