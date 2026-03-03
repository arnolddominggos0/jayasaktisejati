<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VoyageMilestone extends Model
{
    protected $fillable = [
        'id',
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

    protected static function booted(): void
    {
        static::saving(function ($milestone) {

            if ($milestone->actual_date && $milestone->milestone_date) {

                $milestone->status =
                    $milestone->actual_date <= $milestone->milestone_date
                    ? 'ontime'
                    : 'late';
            }
        });
    }
}
