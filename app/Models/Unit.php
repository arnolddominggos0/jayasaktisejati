<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Unit extends Model
{
    use HasFactory;

    protected $table = 'units';

    protected $fillable = [
        'shipment_id',
        'model_no',
        'reg_no',
        'chassis_no',
        'engine_no',
        'color',
        'do_number',
        'qty',
        'container_display',
        'notes',
    ];

    protected $casts = [
        'qty' => 'integer',
    ];

    public function shipment()
    {
        return $this->belongsTo(Shipment::class, 'shipment_id', 'id');
    }
}
