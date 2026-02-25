<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VoyageMilestone extends Model
{
    protected $fillable = [
        'voyage_id',
        'code',
        'milestone_date',
        'position',
        'speed_knots',
        'note',
    ];

    protected $casts = [
        'milestone_date' => 'date',
    ];

    public function voyage()
    {
        return $this->belongsTo(Voyage::class);
    }
}
