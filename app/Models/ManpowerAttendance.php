<?php

namespace App\Models;

use App\Enums\AttendanceStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ManpowerAttendance extends Model
{
    protected $fillable = [
        'session_id','manpower_id','attendance_status','temperature','bp','has_ppe','remark'
    ];

    protected $casts = [
        'attendance_status' => AttendanceStatus::class,
        'has_ppe'           => 'bool',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(BriefingSession::class, 'session_id');
    }

    public function manpower(): BelongsTo
    {
        return $this->belongsTo(Manpower::class);
    }
}
