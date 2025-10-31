<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vessel extends Model
{
    protected $fillable = ['shipping_line_id', 'name', 'code', 'imo', 'capacity'];

    public function shippingLine(): BelongsTo
    {
        return $this->belongsTo(ShippingLine::class);
    }

    public function voyages(): HasMany
    {
        return $this->hasMany(Voyage::class);
    }
}
