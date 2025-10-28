<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShippingLine extends Model
{
    protected $fillable = [
        'code',
        'name',
        'contact',
        'phone',
        'email'
    ];

    public function vessels(): HasMany
    {
        return $this->hasMany(Vessel::class);
    }
    public function voyages(): HasMany
    {
        return $this->hasMany(Voyage::class);
    }
}
