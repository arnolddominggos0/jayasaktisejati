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
use Illuminate\Support\Facades\DB;

class BriefingSession extends Model
{
    protected $table = 'briefing_sessions';

    protected $fillable = [

        // session
        'appsheet_id',
        'shipment_id', // @deprecated SC.5D.0B — legacy 1:1 key, superseded by briefing_session_shipments pivot
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

    /**
     * @deprecated SC.5D.0B — legacy 1:1 relation keyed on briefing_sessions.shipment_id.
     * Active relation: shipments() BelongsToMany via briefing_session_shipments pivot.
     * Only kept for getDisplayLabelAttribute() fallback on pre-SC.3B.19 records.
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
    | YARD COUNT — DUAL SOURCE
    |
    | Before YARD_CUTOFF : use stored unit_masuk_yard (legacy / seeded history).
    | From YARD_CUTOFF   : derive from TrackStatus::Handover (SC.5C.5-B).
    |
    | SQL helper: effectiveUnitSqlExpression() for use in raw aggregate queries.
    | Eloquent accessor: getEffectiveUnitMasukYardAttribute() for model instances.
    |--------------------------------------------------------------------------
    */

    const YARD_CUTOFF = '2026-06-18';

    /**
     * SQL CASE expression that resolves the effective unit count per session row.
     * Safe to embed inside SUM(...) or standalone SELECT.
     *
     * @param  string $alias  Outer table alias used in the query (default: 'briefing_sessions')
     */
    public static function effectiveUnitSqlExpression(string $alias = 'briefing_sessions'): string
    {
        $cutoff = self::YARD_CUTOFF;

        return "CASE
            WHEN {$alias}.date::date < '{$cutoff}'::date
            THEN COALESCE({$alias}.unit_masuk_yard, 0)
            ELSE (
                SELECT COUNT(u.id)
                FROM units u
                JOIN shipments s ON s.id = u.shipment_id
                    AND s.assigned_depot_id = {$alias}.depot_id
                    AND s.status != 'draft'
                JOIN shipment_tracks st ON st.shipment_id = s.id
                    AND st.status = 'handover'
                    AND st.tracked_at IS NOT NULL
                    AND st.tracked_at::date = {$alias}.date
            )
        END";
    }

    /**
     * Effective unit masuk yard for a single BriefingSession model instance.
     * Pre-cutoff: stored value with regex fallback from notes (AppSheet legacy).
     * Post-cutoff: live count via Handover tracks.
     */
    public function getEffectiveUnitMasukYardAttribute(): int
    {
        $date = $this->date instanceof \Carbon\Carbon
            ? $this->date->format('Y-m-d')
            : (string) $this->date;

        if ($date < self::YARD_CUTOFF) {
            if ($this->unit_masuk_yard !== null) {
                return (int) $this->unit_masuk_yard;
            }
            if ($this->notes && preg_match('/Unit Masuk Yard\/PDC:\s*(\d+)/i', $this->notes, $m)) {
                return (int) $m[1];
            }
            return 0;
        }

        if (! $this->depot_id || ! $this->date) {
            return 0;
        }

        return (int) DB::table('units as u')
            ->join('shipments as s', 's.id', '=', 'u.shipment_id')
            ->join('shipment_tracks as st', function ($j) use ($date) {
                $j->on('st.shipment_id', '=', 's.id')
                  ->where('st.status', 'handover')
                  ->whereNotNull('st.tracked_at')
                  ->whereDate('st.tracked_at', $date);
            })
            ->where('s.assigned_depot_id', $this->depot_id)
            ->where('s.status', '!=', 'draft')
            ->count('u.id');
    }

    /*
    |--------------------------------------------------------------------------
    | SHIPMENT READINESS ACCESSORS
    |
    | expected_unit      — total units from all attached shipments (via pivot)
    | actual_unit_masuk_yard — alias for effective_unit_masuk_yard (post-cutoff derived)
    | unit_gap           — expected minus actual, floored at 0
    |--------------------------------------------------------------------------
    */

    public function getExpectedUnitAttribute(): int
    {
        return $this->shipments()
            ->withCount('units')
            ->get()
            ->sum('units_count');
    }

    public function getActualUnitMasukYardAttribute(): int
    {
        return $this->effective_unit_masuk_yard;
    }

    public function getUnitGapAttribute(): int
    {
        return max(0, $this->expected_unit - $this->effective_unit_masuk_yard);
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
        $date  = $this->date?->format('Y-m-d') ?? '-';
        $depot = $this->depot?->name ?? '-';

        return "{$date} · {$depot}";
    }

    /**
     * Count MP who are operationally ready (Siap Kerja) using a DB query.
     *
     * Mirrors getFinalMpStatusAttribute() === 'Siap Kerja' without loading models:
     *   present + has_ppe=true + (recheck_result=FIT  OR  fit_status=FIT with no recheck)
     *
     * This is the single source of truth for all readiness calculations.
     * Use this instead of where('fit_status','FIT') everywhere.
     */
    public function readyManpowerCount(): int
    {
        return (int) $this->attendances()
            ->where('attendance_status', 'present')
            ->where('has_ppe', true)
            ->where(fn ($q) => $q
                ->where('recheck_result', 'FIT')
                ->orWhere(fn ($inner) => $inner
                    ->where('fit_status', 'FIT')
                    ->whereNull('recheck_result')
                )
            )
            ->count();
    }

    public function isOperationallyReady(): bool
    {
        $target = (int) $this->summary_headcount;
        return $target > 0 && $this->readyManpowerCount() >= $target;
    }

    public function refreshSufficientFlag(): void
    {
        $this->summary_sufficient = $this->isOperationallyReady();
        $this->saveQuietly();
    }

    /*
    |--------------------------------------------------------------------------
    | BOOT
    |--------------------------------------------------------------------------
    */

    protected static function booted()
    {
        static::saving(function (self $session) {
            $session->summary_sufficient = $session->isOperationallyReady();
        });
    }
}
