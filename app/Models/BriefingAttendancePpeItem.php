<?php

namespace App\Models;

use App\Enums\PpeCondition;
use App\Enums\PpeType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BriefingAttendancePpeItem extends Model
{
    protected $fillable = [
        'attendance_id',
        'ppe_type',
        'condition',
        'remark'
    ];

    protected $casts = [
        'ppe_type' => PpeType::class,
        'condition' => PpeCondition::class,
    ];

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(BriefingAttendance::class, 'attendance_id');
    }
}
