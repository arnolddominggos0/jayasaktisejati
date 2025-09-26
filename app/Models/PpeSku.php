<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PpeSku extends Model
{
    protected $fillable = [
        'code',
        'name',
        'type',
        'brand',
        'model',
        'size',
        'is_serialized',
        'min_qty',
    ];

    public function items()
    {
        return $this->hasMany(PpeItem::class);
    }
}
