<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TamMonthlySchedule extends Model
{
    protected $fillable = [
        'period_month',
        'version',
        'status',
        'total_plan',
        'draft_message',
        'draft_path',
        'final_path',
        'generated_by_name',
        'generated_at',
        'finalized_at',
    ];

    protected $casts = [
        'period_month'  => 'date',
        'generated_at'  => 'datetime',
        'finalized_at'  => 'datetime',
    ];
}
