<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PpeAssignment extends Model
{
    protected $fillable = [
        'ppe_item_id',
        'manpower_id',
        'assigned_at',
        'returned_at',
        'note'
    ];

    public function item()
    {
        return $this->belongsTo(PpeItem::class, 'ppe_item_id');
    }

    public function manpower()
    {
        return $this->belongsTo(Manpower::class, 'manpower_id');
    }

    public function scopeActive($q)
    {
        return $q->whereNull('returned_at');
    }
}
