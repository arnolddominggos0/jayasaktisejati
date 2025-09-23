<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArmadaAssignment extends Model
{
    protected $fillable = [
        'armada_id',
        'shipment_id',
        'manpower_id',
        'role',
        'status',
        'started_at',
        'ended_at',
        'branch_id',
        'depot_id',
        'date',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'date'       => 'date',
        'started_at' => 'datetime',
        'ended_at'   => 'datetime',
    ];

    public function armada(): BelongsTo
    {
        return $this->belongsTo(Armada::class);
    }
    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }
    public function manpower(): BelongsTo
    {
        return $this->belongsTo(Manpower::class);
    }
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
    public function depot(): BelongsTo
    {
        return $this->belongsTo(Depot::class);
    }
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
