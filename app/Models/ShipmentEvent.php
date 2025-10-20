<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShipmentEvent extends Model
{
    protected $fillable = [
        'shipment_id',
        'type',
        'planned_at',
        'actual_at',
        'location',
        'ref_no',
        'has_issue',
        'issue_type',
        'remarks',
        'data',
        'created_by',
        'updated_by',
    ];
    protected $casts = [
        'planned_at' => 'datetime',
        'actual_at'  => 'datetime',
        'has_issue'  => 'boolean',
        'data'       => 'array',
    ];

    public function shipment()
    {
        return $this->belongsTo(Shipment::class);
    }
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
