<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VoyageDelayLog extends Model
{
    protected $fillable = [
        'voyage_id',
        'old_etd',
        'new_etd',
        'old_eta',
        'new_eta',
        'reason',
        'changed_by',
        'snapshot_before',
        'snapshot_after',
    ];

    protected $casts = [
        'old_etd' => 'datetime',
        'new_etd' => 'datetime',
        'old_eta' => 'datetime',
        'new_eta' => 'datetime',
        'snapshot_before' => 'array',
        'snapshot_after' => 'array',
    ];

    public function voyage()
    {
        return $this->belongsTo(Voyage::class);
    }
}
