<?php

namespace App\Filament\FC\Pages\Dashboard;

use App\Enums\MPCheckStatus;
use App\Enums\ShipmentStatus;
use App\Filament\FC\Pages\MpReadinessMonitoring;
use App\Filament\FC\Resources\BriefingSessionResource;
use App\Models\Branch;
use App\Models\BriefingSession;
use App\Models\Depot;
use App\Models\Shipment;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Dashboard FC — monitoring cepat harian.
 *
 * Prinsip: READ ONLY.
 * Tidak ada form / input operasional di sini.
 * Seluruh action (Mulai Briefing, Input Container, dst.) ada di Monitoring Operasional.
 */
class Dashboard extends Page
{
    protected static ?string $slug = 'dashboard';

    protected static ?string $navigationIcon  = 'heroicon-m-home';
    protected static ?string $navigationLabel = 'Dashboard';
    protected static ?int    $navigationSort  = 10;

    protected static ?string $title = 'Dashboard';

    protected static string $view = 'filament.fc.pages.dashboard';

    public static function canAccess(): bool
    {
        return Filament::auth()->user()?->hasRole('field_coordinator') ?? false;
    }

    public function getTitle(): string|Htmlable
    {
        return static::$title ?? 'Dashboard';
    }

    // ── Context helpers ───────────────────────────────────────────────────────

    public function getBranchContext(): ?Branch
    {
        $user = Filament::auth()->user();
        if (! $user) return null;

        $branchId = app()->bound('scope.branch_id')
            ? app('scope.branch_id')
            : ($user->effectiveBranchId() ?? null);

        return $branchId ? Branch::find($branchId) : null;
    }

    private ?Depot $_depotContext       = null;
    private bool   $_depotContextLoaded = false;

    public function getDepotContext(): ?Depot
    {
        if ($this->_depotContextLoaded) {
            return $this->_depotContext;
        }

        $this->_depotContextLoaded = true;

        $user = Filament::auth()->user();
        if (! $user) {
            return $this->_depotContext = null;
        }

        $depotId = app()->bound('scope.depot_id')
            ? app('scope.depot_id')
            : ($user->scope_unit_type === 'depot' ? $user->scope_unit_id : null);

        if (! $depotId) {
            $depotId = Depot::where('coordinator_user_id', $user->id)->value('id');
        }

        return $this->_depotContext = ($depotId ? Depot::find($depotId) : null);
    }

    public function getBranchName(): string  { return $this->getBranchContext()?->name ?? 'Branch tidak diketahui'; }
    public function getDepotName(): string   { return $this->getDepotContext()?->name  ?? 'Depot tidak diketahui'; }
    public function hasBranchContext(): bool { return $this->getBranchContext() !== null; }
    public function hasDepotContext(): bool  { return $this->getDepotContext()  !== null; }

    // ── Shipment urgency badge ────────────────────────────────────────────────

    public function getUrgencyCount(): int
    {
        $user = Filament::auth()->user();
        if (! $user) return 0;

        $depotId = app()->bound('scope.depot_id') ? (int) app('scope.depot_id') : null;
        $userId  = (int) $user->id;

        $query = Shipment::query()
            ->where('mode', 'sea')
            ->whereNotIn('status', [ShipmentStatus::Delivered->value, ShipmentStatus::Cancelled->value]);

        if ($depotId) {
            $query->where(function ($w) use ($depotId, $userId) {
                $w->where('assigned_depot_id', $depotId)
                  ->orWhere('coordinator_id', $userId);
            });
        } else {
            $query->where('coordinator_id', $userId);
        }

        return $query
            ->where(fn (Builder $q) => $q
                ->where('priority', 'urgent')
                ->orWhere('status', ShipmentStatus::Hold->value)
                ->orWhere(fn (Builder $q2) => $q2->whereNotNull('eta')->where('eta', '<=', now()->addDay()))
            )
            ->count();
    }

    // ── Briefing hari ini ─────────────────────────────────────────────────────

    /**
     * Session-level cache — satu query per request.
     */
    private ?BriefingSession $_todaySession = null;
    private bool $_todaySessionLoaded       = false;

    protected function getTodayBriefingSession(): ?BriefingSession
    {
        if ($this->_todaySessionLoaded) {
            return $this->_todaySession;
        }

        $depotId = $this->getDepotContext()?->id;
        $this->_todaySessionLoaded = true;

        if (! $depotId) {
            return $this->_todaySession = null;
        }

        // Today's briefing session for this depot — prefers active (non-cleared), falls back to any.
        $this->_todaySession = BriefingSession::query()
            ->where('depot_id', $depotId)
            ->whereDate('date', Carbon::today())
            ->whereNotIn('mp_check_status', ['cleared', 'approved'])
            ->select('id', 'summary_headcount', 'summary_sufficient', 'unit_masuk_yard', 'mp_check_status')
            ->latest()
            ->first();

        $this->_todaySession ??= BriefingSession::whereDate('date', Carbon::today())
            ->where('depot_id', $depotId)
            ->select('id', 'summary_headcount', 'summary_sufficient', 'unit_masuk_yard', 'mp_check_status')
            ->first();

        return $this->_todaySession;
    }

    /**
     * Status briefing hari ini — dipakai banner atas.
     *
     * Return keys:
     *   has_briefing   bool
     *   fit_count      int    (hanya jika has_briefing)
     *   need_mp        int    (hanya jika has_briefing)
     *   is_ready       bool   (hanya jika has_briefing) — summary_sufficient
     *   create_url     string (hanya jika !has_briefing) — langsung ke BriefingSessionResource create
     *   view_url       string (hanya jika has_briefing)  — baca detail, bukan edit
     *   monitoring_url string — selalu ada, link ke Monitoring Operasional
     */
    public function getTodayBriefingStatus(): array
    {
        $session       = $this->getTodayBriefingSession();
        $monitoringUrl = MpReadinessMonitoring::getUrl();

        if (! $session) {
            return [
                'has_briefing'   => false,
                'create_url'     => BriefingSessionResource::getUrl('create'),
                'monitoring_url' => $monitoringUrl,
            ];
        }

        $fitCount = $session->readyManpowerCount();

        $mpStatus    = $session->mp_check_status instanceof MPCheckStatus
            ? $session->mp_check_status
            : MPCheckStatus::tryFrom((string) $session->mp_check_status);
        $statusLabel = $mpStatus?->label() ?? (string) $session->mp_check_status;

        return [
            'has_briefing'   => true,
            'fit_count'      => $fitCount,
            'need_mp'        => (int) ($session->summary_headcount ?? 0),
            'is_ready'       => (bool) $session->summary_sufficient,
            'status_label'   => $statusLabel,
            'view_url'       => BriefingSessionResource::getUrl('view', ['record' => $session->id]),
            'monitoring_url' => $monitoringUrl,
        ];
    }

    /**
     * Readiness badge di context header — MP only (summary_sufficient).
     */
    public function getOperationalReadinessBadge(): array
    {
        $session = $this->getTodayBriefingSession();

        if (! $session) {
            return [
                'label' => 'Belum Ada Sesi Briefing',
                'color' => 'gray',
                'icon'  => 'heroicon-m-clock',
            ];
        }

        $isReady = (bool) $session->summary_sufficient;

        return [
            'label' => $isReady ? 'Operasional: SIAP' : 'Operasional: BELUM SIAP',
            'color' => $isReady ? 'success' : 'danger',
            'icon'  => $isReady ? 'heroicon-m-check-circle' : 'heroicon-m-exclamation-triangle',
        ];
    }

    /**
     * Operational Readiness Hari Ini — gabungan MP + Container.
     *
     * Rule: READY hanya jika MP READY AND Container READY.
     * null = belum ada data untuk komponen tersebut.
     *
     * Return keys:
     *   mp_ready        bool|null
     *   container_ready bool|null
     *   overall         bool|null
     *   has_mp          bool
     *   has_container   bool
     */
    public function getTodayOperationalReadiness(): array
    {
        $session    = $this->getTodayBriefingSession();
        $mpReady    = $session !== null ? (bool) $session->summary_sufficient : null;

        $cRow = DB::table('container_readiness_sessions')
            ->whereDate('session_date', Carbon::today())
            ->select('summary_sufficient')
            ->first();

        $containerReady = $cRow !== null ? (bool) $cRow->summary_sufficient : null;

        if ($mpReady === null && $containerReady === null) {
            $overall = null;
        } elseif ($mpReady === false || $containerReady === false) {
            $overall = false;
        } else {
            $overall = true;
        }

        return [
            'mp_ready'        => $mpReady,
            'container_ready' => $containerReady,
            'overall'         => $overall,
            'has_mp'          => $session !== null,
            'has_container'   => $cRow !== null,
        ];
    }

    // ── Unit Butuh Tindakan ───────────────────────────────────────────────────

    public function getUnitsNeedingAction(): array
    {
        $depotId = $this->getDepotContext()?->id;
        if (! $depotId) return ['waiting' => 0, 'bermasalah' => 0, 'total' => 0];

        $result = DB::table('units')
            ->join('shipments as s', 's.id', '=', 'units.shipment_id')
            ->where('s.assigned_depot_id', $depotId)
            ->whereExists(fn ($q) => $q
                ->from('shipment_tracks as st_h')
                ->whereColumn('st_h.shipment_id', 's.id')
                ->where('st_h.status', 'handover')
                ->whereNotNull('st_h.tracked_at')
            )
            ->whereRaw("NOT EXISTS (SELECT 1 FROM shipment_tracks st_exit
                WHERE st_exit.shipment_id = s.id
                AND st_exit.tracked_at IS NOT NULL
                AND (
                    (s.mode IN ('sea', 'sea_freight') AND s.vehicle_loading IN ('rack', 'flat_rack') AND st_exit.status = 'delivery_to_port')
                    OR (NOT (s.mode IN ('sea', 'sea_freight') AND s.vehicle_loading IN ('rack', 'flat_rack')) AND st_exit.status = 'stuffing')
                ))"
            )
            ->selectRaw("
                SUM(CASE WHEN NOT EXISTS (
                    SELECT 1 FROM unit_inspections ui
                    WHERE ui.unit_id = units.id AND ui.stage = 'handover_depot' AND ui.submitted_at IS NOT NULL
                ) THEN 1 ELSE 0 END)::int AS waiting,
                SUM(CASE WHEN EXISTS (
                    SELECT 1 FROM unit_inspections ui
                    WHERE ui.unit_id = units.id AND ui.stage = 'handover_depot' AND ui.gate_decision = 'return_to_pdc'
                ) THEN 1 ELSE 0 END)::int AS bermasalah
            ")
            ->first();

        $waiting    = (int) ($result?->waiting    ?? 0);
        $bermasalah = (int) ($result?->bermasalah ?? 0);

        return [
            'waiting'    => $waiting,
            'bermasalah' => $bermasalah,
            'total'      => $waiting + $bermasalah,
        ];
    }

    // ── Aktivitas Hari Ini — 4 KPI dalam 1 query ─────────────────────────────

    /**
     * Empat KPI operasional harian, satu query agregasi.
     *
     * A — handover_today   : unit dengan handover track::date = hari ini
     * B — ready_loading    : unit di yard, inspeksi accept/allow, belum exit
     *                        (source: same as Monitoring tab Ready Loading)
     * C — loading_today    : unit yang exit gate::date = hari ini
     *                        (source: same exit rule as YardInventoryService)
     * D — problematic_today: unit dengan return_to_pdc created hari ini
     */
    public function getTodayActivityKpis(): array
    {
        $depotId = $this->getDepotContext()?->id;
        if (! $depotId) {
            return [
                'handover_today'    => 0,
                'ready_loading'     => 0,
                'loading_today'     => 0,
                'problematic_today' => 0,
            ];
        }

        // Exit gate EXISTS — same rule as MpReadinessMonitoring::exitNotExistsClosure()
        $exitGateExists = "EXISTS (
            SELECT 1 FROM shipment_tracks st_exit
            WHERE st_exit.shipment_id = s.id
            AND   st_exit.tracked_at IS NOT NULL
            AND (
                (s.mode IN ('sea', 'sea_freight') AND s.vehicle_loading IN ('rack', 'flat_rack') AND st_exit.status = 'delivery_to_port')
                OR (NOT (s.mode IN ('sea', 'sea_freight') AND s.vehicle_loading IN ('rack', 'flat_rack')) AND st_exit.status = 'stuffing')
            ))";

        $row = DB::selectOne("
            SELECT
                -- A. Unit Masuk Yard hari ini (handover tracked today)
                (SELECT COUNT(u.id)
                 FROM units u
                 JOIN shipments s ON s.id = u.shipment_id
                 WHERE s.assigned_depot_id = :depot_a
                   AND EXISTS (
                       SELECT 1 FROM shipment_tracks st_h
                       WHERE st_h.shipment_id = s.id
                         AND st_h.status = 'handover'
                         AND st_h.tracked_at::date = CURRENT_DATE
                   )
                ) AS handover_today,

                -- B. Unit Ready Loading (in yard, inspeksi ok, belum exit)
                (SELECT COUNT(u.id)
                 FROM units u
                 JOIN shipments s ON s.id = u.shipment_id
                 WHERE s.assigned_depot_id = :depot_b
                   AND EXISTS (
                       SELECT 1 FROM shipment_tracks st_h
                       WHERE st_h.shipment_id = s.id AND st_h.status = 'handover' AND st_h.tracked_at IS NOT NULL
                   )
                   AND EXISTS (
                       SELECT 1 FROM unit_inspections ui
                       WHERE ui.unit_id = u.id
                         AND ui.stage = 'handover_depot'
                         AND ui.submitted_at IS NOT NULL
                         AND ui.gate_decision IN ('accept', 'allow_with_remark')
                   )
                   AND NOT {$exitGateExists}
                ) AS ready_loading,

                -- C. Unit Loading Hari Ini (exit gate tracked today)
                (SELECT COUNT(u.id)
                 FROM units u
                 JOIN shipments s ON s.id = u.shipment_id
                 WHERE s.assigned_depot_id = :depot_c
                   AND EXISTS (
                       SELECT 1 FROM shipment_tracks st_exit
                       WHERE st_exit.shipment_id = s.id
                         AND st_exit.tracked_at IS NOT NULL
                         AND st_exit.tracked_at::date = CURRENT_DATE
                         AND (
                             (s.mode IN ('sea', 'sea_freight') AND s.vehicle_loading IN ('rack', 'flat_rack') AND st_exit.status = 'delivery_to_port')
                             OR (NOT (s.mode IN ('sea', 'sea_freight') AND s.vehicle_loading IN ('rack', 'flat_rack')) AND st_exit.status = 'stuffing')
                         )
                   )
                ) AS loading_today,

                -- D. Unit Bermasalah Hari Ini (return_to_pdc created today)
                (SELECT COUNT(u.id)
                 FROM units u
                 JOIN shipments s ON s.id = u.shipment_id
                 WHERE s.assigned_depot_id = :depot_d
                   AND EXISTS (
                       SELECT 1 FROM unit_inspections ui
                       WHERE ui.unit_id = u.id
                         AND ui.stage = 'handover_depot'
                         AND ui.gate_decision = 'return_to_pdc'
                         AND ui.created_at::date = CURRENT_DATE
                   )
                ) AS problematic_today
        ", [
            'depot_a' => $depotId,
            'depot_b' => $depotId,
            'depot_c' => $depotId,
            'depot_d' => $depotId,
        ]);

        return [
            'handover_today'    => (int) ($row?->handover_today    ?? 0),
            'ready_loading'     => (int) ($row?->ready_loading     ?? 0),
            'loading_today'     => (int) ($row?->loading_today     ?? 0),
            'problematic_today' => (int) ($row?->problematic_today ?? 0),
        ];
    }

    /**
     * Preview max 5 units paling lama menunggu inspeksi (untuk dashboard action panel).
     * Menggunakan query yang sama dengan buildWaitingInspectionTable di Monitoring Operasional.
     */
    public function getWaitingInspectionPreview(): array
    {
        $depotId = $this->getDepotContext()?->id;
        if (! $depotId) return [];

        return DB::table('units')
            ->select([
                'units.sjkb_no',
                DB::raw('s.code AS shipment_code'),
                DB::raw('st_h.tracked_at AS handover_at'),
                DB::raw('(CURRENT_DATE - st_h.tracked_at::date)::int AS waiting_days'),
            ])
            ->join('shipments as s', 's.id', '=', 'units.shipment_id')
            ->where('s.assigned_depot_id', $depotId)
            ->join('shipment_tracks as st_h', function ($j) {
                $j->on('st_h.shipment_id', '=', 's.id')
                  ->where('st_h.status', 'handover')
                  ->whereNotNull('st_h.tracked_at');
            })
            ->whereRaw("NOT EXISTS (SELECT 1 FROM shipment_tracks st_exit
                WHERE st_exit.shipment_id = s.id AND st_exit.tracked_at IS NOT NULL
                AND (
                    (s.mode IN ('sea', 'sea_freight') AND s.vehicle_loading IN ('rack', 'flat_rack') AND st_exit.status = 'delivery_to_port')
                    OR (NOT (s.mode IN ('sea', 'sea_freight') AND s.vehicle_loading IN ('rack', 'flat_rack')) AND st_exit.status = 'stuffing')
                ))"
            )
            ->whereNotExists(fn ($q) => $q
                ->from('unit_inspections as ui')
                ->whereColumn('ui.unit_id', 'units.id')
                ->where('ui.stage', 'handover_depot')
                ->whereNotNull('ui.submitted_at')
            )
            ->orderBy('st_h.tracked_at', 'asc')
            ->limit(5)
            ->get()
            ->map(fn ($r) => [
                'sjkb_no'       => $r->sjkb_no ?? '—',
                'shipment_code' => $r->shipment_code ?? '—',
                'waiting_days'  => (int) ($r->waiting_days ?? 0),
                'waiting_label' => ((int) ($r->waiting_days ?? 0)) === 0
                    ? 'Hari ini'
                    : ((int) $r->waiting_days) . ' hari',
            ])
            ->toArray();
    }

    /**
     * Preview max 5 unit bermasalah (gate_decision = return_to_pdc) paling tua.
     * Menggunakan query yang sama dengan buildBermasalahTable di Monitoring Operasional.
     */
    public function getBermasalahPreview(): array
    {
        $depotId = $this->getDepotContext()?->id;
        if (! $depotId) return [];

        return DB::table('units')
            ->select([
                'units.sjkb_no',
                DB::raw('s.code AS shipment_code'),
                DB::raw('ui.notes AS remark'),
                DB::raw('(CURRENT_DATE - ui.submitted_at::date)::int AS aging_days'),
            ])
            ->join('shipments as s', 's.id', '=', 'units.shipment_id')
            ->where('s.assigned_depot_id', $depotId)
            ->join('shipment_tracks as st_h', function ($j) {
                $j->on('st_h.shipment_id', '=', 's.id')
                  ->where('st_h.status', 'handover')
                  ->whereNotNull('st_h.tracked_at');
            })
            ->join('unit_inspections as ui', function ($j) {
                $j->on('ui.unit_id', '=', 'units.id')
                  ->where('ui.stage', 'handover_depot')
                  ->where('ui.gate_decision', 'return_to_pdc');
            })
            ->whereRaw("NOT EXISTS (SELECT 1 FROM shipment_tracks st_exit
                WHERE st_exit.shipment_id = s.id AND st_exit.tracked_at IS NOT NULL
                AND (
                    (s.mode IN ('sea', 'sea_freight') AND s.vehicle_loading IN ('rack', 'flat_rack') AND st_exit.status = 'delivery_to_port')
                    OR (NOT (s.mode IN ('sea', 'sea_freight') AND s.vehicle_loading IN ('rack', 'flat_rack')) AND st_exit.status = 'stuffing')
                ))"
            )
            ->orderBy('ui.submitted_at', 'asc')
            ->limit(5)
            ->get()
            ->map(fn ($r) => [
                'sjkb_no'       => $r->sjkb_no ?? '—',
                'shipment_code' => $r->shipment_code ?? '—',
                'remark'        => filled($r->remark) ? \Illuminate\Support\Str::limit($r->remark, 30) : 'Return to PDC',
                'aging_days'    => (int) ($r->aging_days ?? 0),
            ])
            ->toArray();
    }
}
