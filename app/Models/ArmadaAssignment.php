<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArmadaAssignment extends Model
{
    protected $fillable = ['armada_id','shipment_id','date','notes'];

    protected $casts = ['date' => 'date'];

    public function armada(): BelongsTo
    {
        return $this->belongsTo(Armada::class);
    }

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }
}
