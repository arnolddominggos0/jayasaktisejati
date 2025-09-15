<?php

namespace App\Models;

use App\Enums\DepotMetric;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DepotActivity extends Model
{
    protected $fillable = [
        'depot_id',
        'date',
        'metric',
        'value',
        'remark'
    ];

    protected $casts = [
        'date' => 'date',
        'metric' => DepotMetric::class,
    ];

    public function depot(): BelongsTo
    {
        return $this->belongsTo(Depot::class);
    }
}
