<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vessel extends Model
{
    protected $fillable = ['name', 'shipping_line_id', 'imo', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function shippingLine(): BelongsTo
    {
        return $this->belongsTo(ShippingLine::class);
    }

    public function voyages(): HasMany
    {
        return $this->hasMany(Voyage::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(ShippingSchedule::class);
    }
}
