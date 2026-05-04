<?php

namespace App\Models;

use App\Enums\AttendanceStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BriefingAttendance extends Model
{
    protected $table = 'briefing_attendances';

    protected $fillable = [
        'session_id',
        'manpower_id',
        'ppeitem_id',
        'attendance_status',
        'temperature',
        'bp_systolic',
        'bp_diastolic',
        'has_ppe',
        'remark',
        'health_complaint',
        'created_by',
        'rest_started_at',
        'recheck_result',
    ];

    protected $casts = [
        'has_ppe' => 'boolean',
        'attendance_status' => AttendanceStatus::class,
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(BriefingSession::class, 'session_id');
    }

    public function manpower(): BelongsTo
    {
        return $this->belongsTo(Manpower::class, 'manpower_id');
    }
    public function ppeItems()
    {
        return $this->hasMany(\App\Models\BriefingAttendancePpeItem::class, 'attendance_id');
    }

    public function ppeInspections()
    {
        return $this->hasMany(\App\Models\PpeInspection::class, 'briefing_attendance_id');
    }

    public function getPpeAllGoodAttribute(): bool
    {
        $items = $this->ppeItems()->pluck('condition', 'ppe_type');
        return $items->count() >= 4 && $items->every(fn($c) => $c === \App\Enums\PpeCondition::Baik);
    }
    public function getBpAttribute(): ?string
    {
        if ($this->bp_systolic && $this->bp_diastolic) {
            return "{$this->bp_systolic}/{$this->bp_diastolic}";
        }

        return null;
    }

    public function setBpAttribute(?string $value): void
    {
        if (! $value) {
            $this->bp_systolic = null;
            $this->bp_diastolic = null;
            return;
        }

        if (preg_match('/^\s*(\d{2,3})\s*\/\s*(\d{2,3})\s*$/', $value, $m)) {
            $this->bp_systolic = (int) $m[1];
            $this->bp_diastolic = (int) $m[2];
        }
    }

    protected static function booted()
    {
        static::saved(function ($attendance) {
            $session = $attendance->session;

            if (!$session) return;

            $presentCount = $session->attendances()
                ->where('attendance_status', 'present')
                ->count();

            $session->summary_sufficient =
                $presentCount >= $session->summary_headcount;

            $session->saveQuietly();
        });
    }
}
