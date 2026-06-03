<?php

namespace App\Models;

use App\Enums\AttendanceStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BriefingAttendance extends Model
{
    protected $table = 'briefing_attendances';

    protected $fillable = [

        // relations
        'session_id',
        'manpower_id',

        // attendance
        'attendance_status',
	'mp_type',
	'backup_name',

        // health check
        'temperature',
        'bp_systolic',
        'bp_diastolic',
        'health_complaint',

        // fit status
        'fit_status',

        // recheck
        'recheck_required',
        'rest_started_at',
        'recheck_result',

        // medical
        'medical_action',

        // APD
        'has_ppe',
        'personal_ppe_status',

        // notes
        'remark',

        // signature
        'signature_path',

        // audit
        'created_by',
    ];

    protected $casts = [
        'has_ppe' => 'boolean',
        'recheck_required' => 'boolean',

        'attendance_status' => AttendanceStatus::class,

        'rest_started_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

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
        return $this->hasMany(
            BriefingAttendancePpeItem::class,
            'attendance_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS
    |--------------------------------------------------------------------------
    */

    public function getBpAttribute(): ?string
    {
        if ($this->bp_systolic && $this->bp_diastolic) {
            return "{$this->bp_systolic}/{$this->bp_diastolic}";
        }

        return null;
    }

    public function setBpAttribute(?string $value): void
    {
        if (!$value) {

            $this->bp_systolic = null;
            $this->bp_diastolic = null;

            return;
        }

        if (preg_match('/^\s*(\d{2,3})\s*\/\s*(\d{2,3})\s*$/', $value, $m)) {

            $this->bp_systolic = (int) $m[1];
            $this->bp_diastolic = (int) $m[2];
        }
    }

    /*
    |--------------------------------------------------------------------------
    | HELPERS
    |--------------------------------------------------------------------------
    */

    public function getPpeAllGoodAttribute(): bool
    {
        $items = $this->ppeItems()
            ->pluck('condition', 'ppe_type');

        return $items->count() >= 4
            && $items->every(
                fn($c) => $c === \App\Enums\PpeCondition::Baik
            );
    }

    /*
    |--------------------------------------------------------------------------
    | BOOT
    |--------------------------------------------------------------------------
    */

    protected static function booted()
    {
        static::saved(function ($attendance) {

            $session = $attendance->session;

            if (!$session) {
                return;
            }

            $fitCount = $session->attendances()
                ->where('fit_status', 'FIT')
                ->count();

            $session->summary_sufficient =
                $session->summary_headcount > 0 && $fitCount >= $session->summary_headcount;

            $session->saveQuietly();
        });
    }
}
