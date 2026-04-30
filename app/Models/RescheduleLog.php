<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RescheduleLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'voyage_id',
        'old_etd',
        'new_etd',
        'old_eta',
        'new_eta',
        'reason',
        'changed_by',
        'changed_by_name',
    ];

    protected $casts = [
        'old_etd' => 'datetime',
        'new_etd' => 'datetime',
        'old_eta' => 'datetime',
        'new_eta' => 'datetime',
        'changed_by' => 'integer',
    ];

    public function voyage()
    {
        return $this->belongsTo(Voyage::class);
    }
}
