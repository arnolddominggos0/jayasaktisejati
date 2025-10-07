<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SeaContainerCargo extends Model
{
    protected $fillable = [
        'container_id',
        'group_type',
        'description',
        'unit_ref',
        'qty',
        'cbm',
        'weight_kg',
    ];

    public function container(): BelongsTo
    {
        return $this->belongsTo(SeaContainer::class, 'container_id');
    }
}
