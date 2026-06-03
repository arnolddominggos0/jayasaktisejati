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

        // mp type
        'mp_type',
        'backup_name',

        // attendance
        'attendance_status',

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

    public function getDisplayNameAttribute(): string
    {
       if (
        strtolower((string) $this->mp_type) === 'backup'
        && filled($this->backup_name)
    ) {
        return $this->backup_name;
    }

        return $this->manpower?->name ?? '-';
    }

    public function getFinalMpStatusAttribute(): string
    {
    $attendanceStatus =
        $this->attendance_status?->value
        ?? $this->attendance_status;

    // Tidak Hadir
    if ($attendanceStatus !== 'present') {
        return 'Tidak Hadir';
    }

    // Siap Kerja (hasil pemeriksaan ulang FIT + APD lengkap)
    if (
        strtoupper((string) $this->recheck_result) === 'FIT'
        && $this->has_ppe
    ) {
        return 'Siap Kerja';
    }

    // Siap Kerja (FIT awal + APD lengkap + belum recheck)
    if (
        strtoupper((string) $this->fit_status) === 'FIT'
        && blank($this->recheck_result)
        && $this->has_ppe
    ) {
        return 'Siap Kerja';
    }

    // APD Tidak Lengkap
    if ($this->has_ppe === false) {
        return 'APD Tidak Lengkap';
    }

    // Istirahat 30 Menit
    if (
        $this->medical_action === 'Istirahat 30 menit'
        && blank($this->recheck_result)
    ) {
        return 'Istirahat 30 Menit';
    }

    // Tidak Fit (hasil recheck final)
    if (
        strtoupper((string) $this->recheck_result) === 'TIDAK FIT'
    ) {
        return 'Tidak Fit';
    }

    // Perlu Pemeriksaan Ulang
    if (
        strtoupper((string) $this->fit_status) === 'TIDAK FIT'
    ) {
        return 'Perlu Pemeriksaan Ulang';
    }

    return 'Belum Dinilai';
    }

    public function getIsBackupAttribute(): bool
    {
        return strtolower((string) $this->mp_type) === 'backup';
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
