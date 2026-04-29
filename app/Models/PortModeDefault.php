<?php

    namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PortModeDefault extends Model
{
    protected $fillable = ['port_id','mode','destination_depot_id'];

    public function port(): BelongsTo { return $this->belongsTo(Port::class); }
    public function destinationDepot(): BelongsTo { return $this->belongsTo(Depot::class, 'destination_depot_id'); }
}

