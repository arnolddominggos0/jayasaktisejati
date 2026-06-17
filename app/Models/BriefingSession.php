<?php

namespace App\Models;

use App\Enums\AttendanceStatus;
use App\Enums\MPBackupType;
use App\Enums\MPCheckStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class BriefingSession extends Model
{
    protected $table = 'briefing_sessions';

    protected $fillable = [

        // session
        'appsheet_id',
        'shipment_id',
        'date',
        'depot_id',
        'coordinator_user_id',

        // operational
        'notes',
        'unit_masuk_yard',

        // manpower summary
        'summary_headcount',
        'summary_sufficient',
        'summary_solution',

        // status
        'mp_check_status',

        // approval
        'approved_at',
        'approved_by',

        // backup MP
        'backup_required',
        'backup_type',
        'backup_notes',

        // pending activity
        'pending_activity',
        'pending_reason',

        // APD request
        'apd_request_status',
        'apd_request_note',

        // evidence
        'briefing_evidence_path',
    ];

    protected $casts = [
        'date' => 'date',

        'summary_sufficient' => 'boolean',

        'backup_required' => 'boolean',
        'pending_activity' => 'boolean',

        'backup_type' => MPBackupType::class,
        'mp_check_status' => MPCheckStatus::class,

        'approved_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class, 'shipment_id');
    }

    public function shipments(): BelongsToMany
    {
        return $this->belongsToMany(
            Shipment::class,
            'briefing_session_shipments',
            'briefing_session_id',
            'shipment_id'
        );
    }

    public function depot(): BelongsTo
    {
        return $this->belongsTo(Depot::class, 'depot_id');
    }

    public function coordinator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'coordinator_user_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(BriefingAttendance::class, 'session_id');
    }

    public function stockApdChecks(): HasMany
    {
        return $this->hasMany(StockApdCheck::class, 'session_id');
    }

    /*
    |--------------------------------------------------------------------------
    | HELPERS
    |--------------------------------------------------------------------------
    */

    public function presentAttendances()
    {
        return $this->hasMany(BriefingAttendance::class, 'session_id')
            ->where('attendance_status', AttendanceStatus::Present->value);
    }

    public function getDisplayLabelAttribute(): string
    {
        if ($this->shipment_id && $this->relationLoaded('shipment') && $this->shipment) {
            return "{$this->shipment->code} · " . ($this->depot?->name ?? '-');
        }

        $date = $this->date?->format('Y-m-d') ?? '-';
        $depot = $this->depot?->name ?? '-';

        return "{$date} · {$depot}";
    }

    public function refreshSufficientFlag(): void
    {
        $fit = $this->attendances()
            ->where('fit_status', 'FIT')
            ->count();

        $target = (int) $this->summary_headcount;

        $this->summary_sufficient =
            $target > 0 && $fit >= $target;

        $this->saveQuietly();
    }

    /*
    |--------------------------------------------------------------------------
    | BOOT
    |--------------------------------------------------------------------------
    */

    protected static function booted()
    {
        static::saving(function ($session) {

            $fit = $session->attendances()
                ->where('fit_status', 'FIT')
                ->count();

            $target = (int) $session->summary_headcount;

            $session->summary_sufficient =
                $target > 0 && $fit >= $target;
        });
    }
}
