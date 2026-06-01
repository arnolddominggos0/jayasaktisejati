<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PpeInspection extends Model
{
    protected $fillable = [
        'briefing_attendance_id',
        'ppe_item_id',
        'type',
        'condition',
        'remark'
    ];

    public function attendance()
    {
        return $this->belongsTo(BriefingAttendance::class, 'briefing_attendance_id');
    }

    public function item()
    {
        return $this->belongsTo(PpeItem::class, 'ppe_item_id');
    }
}
