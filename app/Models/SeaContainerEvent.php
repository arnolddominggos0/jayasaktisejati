<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SeaContainerEvent extends Model
{
    protected $fillable = [
        'container_id',
        'event',
        'event_time',
        'location',
        'remark'
    ];

    protected $casts = ['event_time' => 'datetime'];

    public function container(): BelongsTo
    {
        return $this->belongsTo(SeaContainer::class, 'container_id');
    }
}
