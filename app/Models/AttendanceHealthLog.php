<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceHealthLog extends Model
{
    protected $table = 'attendance_health_logs';

    protected $fillable = [
        'attendance_id',
        'event_type',
        'temperature',
        'bp_systolic',
        'bp_diastolic',
        'medical_action',
        'remark',
        'created_by',
    ];

    protected $casts = [
        'temperature' => 'decimal:1',
        'created_at'  => 'datetime',
    ];

    // ── Relations ─────────────────────────────────────────────────────────────

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(BriefingAttendance::class, 'attendance_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function getEventLabelAttribute(): string
    {
        return match ($this->event_type) {
            'initial_check'   => 'Pemeriksaan Awal',
            'auto_fit'        => 'Evaluasi: FIT',
            'auto_not_fit'    => 'Evaluasi: Tidak FIT',
            'medical_action'  => 'Tindakan Medis',
            'recheck_started' => 'Recheck Dimulai',
            'recheck_fit'     => 'Recheck: FIT',
            'recheck_not_fit' => 'Recheck: Tidak FIT',
            default           => $this->event_type,
        };
    }

    public function getEventColorAttribute(): string
    {
        return match ($this->event_type) {
            'auto_fit', 'recheck_fit'    => 'success',
            'auto_not_fit', 'recheck_not_fit' => 'danger',
            'medical_action'             => 'warning',
            'recheck_started'            => 'info',
            default                      => 'gray',
        };
    }
}
