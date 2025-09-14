<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ManpowerAssignment extends Model
{
    protected $fillable = ['manpower_id','shipment_id','depot_id','date','role_at_task','notes'];

    protected $casts = ['date' => 'date'];

    public function manpower(): BelongsTo
    {
        return $this->belongsTo(Manpower::class);
    }

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    public function depot(): BelongsTo
    {
        return $this->belongsTo(Depot::class);
    }
}
