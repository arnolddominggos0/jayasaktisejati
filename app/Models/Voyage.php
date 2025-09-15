<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Voyage extends Model
{
    protected $fillable = [
        'vessel_id','shipping_line_id','voyage_no','port_from_id','port_to_id','etd','eta','service'
    ];

    protected $casts = ['etd' => 'date', 'eta' => 'date'];

    public function shippingLine(): BelongsTo { return $this->belongsTo(ShippingLine::class); }
    public function vessel(): BelongsTo { return $this->belongsTo(Vessel::class); }
    public function portFrom(): BelongsTo { return $this->belongsTo(Port::class, 'port_from_id'); }
    public function portTo(): BelongsTo { return $this->belongsTo(Port::class, 'port_to_id'); }
}
