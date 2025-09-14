<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArmadaMaintenance extends Model
{
    protected $fillable = ['armada_id','title','planned_at','done_at','odometer','cost','notes'];

    protected $casts = [
        'planned_at' => 'date',
        'done_at'    => 'date',
    ];

    public function armada(): BelongsTo
    {
        return $this->belongsTo(Armada::class);
    }
}
