<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PpeItem extends Model
{
    protected $fillable = [
        'ppe_sku_id',
        'serial',
        'status',
        'current_manpower_id',
        'assigned_at'
    ];

    public function sku()
    {
        return $this->belongsTo(PpeSku::class, 'ppe_sku_id');
    }

    public function assignments()
    {
        return $this->hasMany(PpeAssignment::class);
    }

    public function currentManpower()
    {
        return $this->belongsTo(\App\Models\Manpower::class, 'current_manpower_id');
    }
}
