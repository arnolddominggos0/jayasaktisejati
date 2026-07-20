<?php

namespace App\Models;

use App\Enums\AttendanceStatus;
use App\Enums\PpeCondition;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BriefingAttendance extends Model
{
    protected $table = 'briefing_attendances';

    protected $fillable = [

        'appsheet_id',

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
        'recheck_temperature',
        'recheck_bp_systolic',
        'recheck_bp_diastolic',
        'recheck_at',

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
        'has_ppe'          => 'boolean',
        'recheck_required' => 'boolean',

        'attendance_status' => AttendanceStatus::class,

        'rest_started_at' => 'datetime',
        'recheck_at'      => 'datetime',
        'recheck_temperature' => 'decimal:1',
    ];

    // Transient flag — not persisted, used to communicate auto-eval outcome to saved() hook.
    public bool $autoEvaluated         = false;
    public ?string $autoEvaluatedValue = null;

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

    public function healthLogs()
    {
        return $this->hasMany(AttendanceHealthLog::class, 'attendance_id')
            ->orderBy('created_at');
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

        // Istirahat 30 Menit — menunggu recheck, belum final
        if (
            $this->medical_action === 'Istirahat 30 menit'
            && blank($this->recheck_result)
        ) {
            return 'Istirahat 30 Menit';
        }

        // Berobat — terminal, dikirim ke fasilitas medis
        if ($this->medical_action === 'Berobat') {
            return 'Berobat';
        }

        // Dipulangkan — terminal, tidak dapat melanjutkan kerja
        if ($this->medical_action === 'Pulang') {
            return 'Dipulangkan';
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
                fn ($c) => $c === PpeCondition::Baik
            );
    }

    /*
    |--------------------------------------------------------------------------
    | BOOT
    |--------------------------------------------------------------------------
    */

    protected static function booted()
    {
        static::saving(function (self $attendance) {
            // Skip when fit_status is being explicitly set in this save (manual override or AppSheet).
            if ($attendance->isDirty('fit_status')) {
                return;
            }

            // Re-evaluate when: (a) fit_status is still null, or (b) vitals are being updated.
            $vitalsChanged = $attendance->isDirty(['temperature', 'bp_systolic', 'bp_diastolic']);
            if ($attendance->fit_status !== null && ! $vitalsChanged) {
                return;
            }

            static::autoEvaluateFitStatus($attendance);
        });

        static::saved(function (self $attendance) {
            // ── Auto-eval audit log ──────────────────────────────────────────
            if ($attendance->autoEvaluated && $attendance->wasChanged('fit_status')) {
                $userId = auth()->id() ?? null;

                AttendanceHealthLog::create([
                    'attendance_id' => $attendance->id,
                    'event_type'    => 'initial_check',
                    'temperature'   => $attendance->temperature,
                    'bp_systolic'   => $attendance->bp_systolic,
                    'bp_diastolic'  => $attendance->bp_diastolic,
                    'created_by'    => $userId,
                ]);

                AttendanceHealthLog::create([
                    'attendance_id' => $attendance->id,
                    'event_type'    => $attendance->autoEvaluatedValue === 'FIT'
                        ? 'auto_fit'
                        : 'auto_not_fit',
                    'temperature'   => $attendance->temperature,
                    'bp_systolic'   => $attendance->bp_systolic,
                    'bp_diastolic'  => $attendance->bp_diastolic,
                    'created_by'    => $userId,
                ]);

                $attendance->autoEvaluated      = false;
                $attendance->autoEvaluatedValue = null;
            }

            // ── Re-evaluate session readiness (summary_sufficient + mp_check_status) ──
            $session = $attendance->session;

            if (! $session || $session->isTerminal()) {
                return;
            }

            \App\Services\BriefingSessionEvaluator::evaluate($session);
        });

        static::deleted(function (self $attendance) {
            $session = BriefingSession::find($attendance->session_id);
            if ($session && ! $session->isTerminal()) {
                \App\Services\BriefingSessionEvaluator::evaluate($session);
            }
        });
    }

    /**
     * Auto-evaluate fit_status from vitals.
     *
     * Thresholds:
     *   Temperature: 35.5 – 37.2 °C  (≥ 37.3 → recheck territory → TIDAK FIT)
     *   Systolic:    90 – 120 mmHg
     *   Diastolic:   60 – 80  mmHg
     *
     * Sets fit_status = 'FIT' or 'TIDAK FIT'. Returns without setting if not
     * present, or if any vital is missing (keeps NULL → "Belum Dinilai").
     */
    private static function autoEvaluateFitStatus(self $attendance): void
    {
        $attVal = $attendance->attendance_status instanceof AttendanceStatus
            ? $attendance->attendance_status->value
            : (string) ($attendance->attendance_status ?? '');

        if ($attVal !== 'present') {
            return;
        }

        $temp = $attendance->temperature !== null ? (float) $attendance->temperature : null;
        $sys  = $attendance->bp_systolic  !== null ? (int)   $attendance->bp_systolic  : null;
        $dia  = $attendance->bp_diastolic !== null ? (int)   $attendance->bp_diastolic : null;

        if ($temp === null || $sys === null || $dia === null) {
            return; // Incomplete vitals — keep NULL → "Belum Dinilai"
        }

        $tempOk = $temp >= 35.5 && $temp <= 37.2;
        $sysOk  = $sys  >= 90   && $sys  <= 120;
        $diaOk  = $dia  >= 60   && $dia  <= 80;

        $result = ($tempOk && $sysOk && $diaOk) ? 'FIT' : 'TIDAK FIT';

        $attendance->fit_status         = $result;
        $attendance->autoEvaluated      = true;
        $attendance->autoEvaluatedValue = $result;
    }
}
