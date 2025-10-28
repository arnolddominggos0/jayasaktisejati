<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vessel extends Model
{
    protected $fillable = ['name', 'shipping_line_id', 'imo'];

    public function shippingLine(): BelongsTo
    {
        return $this->belongsTo(ShippingLine::class);
    }
    public function voyages(): HasMany
    {
        return $this->hasMany(Voyage::class);
    }
}
