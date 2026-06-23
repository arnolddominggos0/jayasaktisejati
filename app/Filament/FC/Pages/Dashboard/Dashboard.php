<?php

namespace App\Filament\FC\Pages\Dashboard;

use App\Enums\MPCheckStatus;
use App\Filament\FC\Pages\MpReadinessMonitoring;
use App\Filament\FC\Resources\BriefingSessionResource;
use App\Models\Branch;
use App\Models\BriefingSession;
use App\Models\Depot;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

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

    // ── Kesiapan Operasional Hari Ini ────────────────────────────────────────

    /**
     * Dua komponen kesiapan harian:
     *   mp_fit / mp_need       — dari BriefingSession hari ini
     *   container_available    — dari ContainerReadinessSession hari ini
     *
     * Unit Aktif di Yard dipindah ke section tersendiri (getActiveYardUnits).
     */
    public function getKesiapanOperasional(): array
    {
        $session = $this->getTodayBriefingSession();

        $mpFit  = $session ? $session->readyManpowerCount() : null;
        $mpNeed = $session ? (int) ($session->summary_headcount ?? 0) : null;

        $cRow = DB::table('container_readiness_sessions')
            ->whereDate('session_date', Carbon::today())
            ->select('container_available', 'summary_sufficient')
            ->first();

        $containerAvailable = $cRow !== null ? (int) ($cRow->container_available ?? 0) : null;
        $containerReady     = $cRow !== null ? (bool) $cRow->summary_sufficient : null;

        return [
            'mp_fit'              => $mpFit,
            'mp_need'             => $mpNeed,
            'container_available' => $containerAvailable,
            'container_ready'     => $containerReady,
        ];
    }

    // ── Unit Aktif di Yard ────────────────────────────────────────────────────

    /**
     * Daftar unit yang masih dalam tanggung jawab depot asal.
     * Track status yang termasuk: pickup, handover, stuffing, delivery_to_port,
     * stacking, unit_loading.
     * Diurutkan berdasarkan aktivitas terbaru (latest tracked_at DESC).
     */
    public function getActiveYardUnits(): array
    {
        $depotId = $this->getDepotContext()?->id;
        if (! $depotId) return [];

        $rows = DB::select("
            SELECT
                u.sjkb_no,
                s.code      AS shipment_code,
                u.model_no,
                u.chassis_no,
                (
                    SELECT status FROM shipment_tracks
                    WHERE shipment_id = s.id AND tracked_at IS NOT NULL
                    ORDER BY tracked_at DESC LIMIT 1
                ) AS track_status,
                s.voyage,
                (
                    SELECT tracked_at FROM shipment_tracks
                    WHERE shipment_id = s.id AND tracked_at IS NOT NULL
                    ORDER BY tracked_at DESC LIMIT 1
                ) AS latest_tracked_at
            FROM units u
            JOIN shipments s ON s.id = u.shipment_id
            WHERE s.assigned_depot_id = :depot
              AND s.status NOT IN ('draft', 'delivered', 'cancelled')
              AND (
                  SELECT status FROM shipment_tracks
                  WHERE shipment_id = s.id AND tracked_at IS NOT NULL
                  ORDER BY tracked_at DESC LIMIT 1
              ) IN ('pickup', 'handover', 'stuffing', 'delivery_to_port', 'stacking', 'unit_loading')
            ORDER BY latest_tracked_at DESC
        ", ['depot' => $depotId]);

        return collect($rows)->map(function ($r) {
            $status    = \App\Enums\TrackStatus::tryFrom((string) ($r->track_status ?? ''));
            $statusKey = (string) ($r->track_status ?? '');
            return [
                'sjkb_no'          => $r->sjkb_no ?? '—',
                'shipment_code'    => $r->shipment_code ?? '—',
                'unit_label'       => trim(implode(' · ', array_filter([$r->model_no, $r->chassis_no]))) ?: '—',
                'status_label'     => $status?->label() ?? $statusKey ?: '—',
                'status_key'       => $statusKey,
                'next_requirement' => match($statusKey) {
                    'pickup'           => 'Menunggu Handover',
                    'handover'         => 'Menunggu Inspeksi',
                    'stuffing'         => 'Ready Loading',
                    'delivery_to_port' => 'Menuju Pelabuhan',
                    'stacking'         => 'Menunggu Unit Loading',
                    'unit_loading'     => 'Menunggu On Ship',
                    default            => '—',
                },
                'voyage'           => $r->voyage ?? '—',
                'updated_at'       => $r->latest_tracked_at
                    ? Carbon::parse($r->latest_tracked_at)->format('d M H:i')
                    : '—',
            ];
        })->toArray();
    }

    // ── Perlu Perhatian ───────────────────────────────────────────────────────

    /**
     * Dua hitungan shipment yang memerlukan perhatian FC:
     *   bermasalah — shipment dengan unit gate_decision = return_to_pdc (belum exit)
     *   tertahan   — shipment yang blocked oleh track requirement aktif
     *                (handover inspection, loading inspection, loading session rack)
     */
    public function getPerluPerhatian(): array
    {
        $depotId = $this->getDepotContext()?->id;
        if (! $depotId) return ['bermasalah' => 0, 'tertahan' => 0];

        $exitGate = "EXISTS (
            SELECT 1 FROM shipment_tracks st_exit
            WHERE st_exit.shipment_id = s.id
              AND st_exit.tracked_at IS NOT NULL
              AND (
                  (s.mode IN ('sea', 'sea_freight') AND s.vehicle_loading IN ('rack', 'flat_rack') AND st_exit.status = 'delivery_to_port')
                  OR (NOT (s.mode IN ('sea', 'sea_freight') AND s.vehicle_loading IN ('rack', 'flat_rack')) AND st_exit.status = 'stuffing')
              )
        )";

        $row = DB::selectOne("
            SELECT
                -- Shipment Bermasalah: unit with return_to_pdc at handover_depot, not yet through exit gate
                (SELECT COUNT(DISTINCT s.id)::int
                 FROM shipments s
                 WHERE s.assigned_depot_id = :depot_a
                   AND NOT {$exitGate}
                   AND EXISTS (
                       SELECT 1 FROM units u
                       JOIN unit_inspections ui ON ui.unit_id = u.id
                       WHERE u.shipment_id = s.id
                         AND ui.stage = 'handover_depot'
                         AND ui.gate_decision = 'return_to_pdc'
                   )
                ) AS bermasalah,

                -- Shipment Tertahan: blocked at any inspection/loading gate
                (SELECT COUNT(DISTINCT s.id)::int
                 FROM shipments s
                 WHERE s.assigned_depot_id = :depot_b
                   AND s.status NOT IN ('draft', 'delivered', 'cancelled')
                   AND (
                       -- Case 1: At Handover, handover inspection incomplete or rejected
                       (
                           EXISTS (SELECT 1 FROM shipment_tracks WHERE shipment_id = s.id AND status = 'handover' AND tracked_at IS NOT NULL)
                           AND NOT {$exitGate}
                           AND (
                               EXISTS (
                                   SELECT 1 FROM units u WHERE u.shipment_id = s.id
                                   AND NOT EXISTS (
                                       SELECT 1 FROM unit_inspections ui
                                       WHERE ui.unit_id = u.id AND ui.stage = 'handover_depot' AND ui.submitted_at IS NOT NULL
                                   )
                               )
                               OR EXISTS (
                                   SELECT 1 FROM units u JOIN unit_inspections ui ON ui.unit_id = u.id
                                   WHERE u.shipment_id = s.id AND ui.stage = 'handover_depot' AND ui.gate_decision = 'return_to_pdc'
                               )
                           )
                       )
                       OR
                       -- Case 2: At Stuffing (non-rack), loading inspection incomplete or rejected
                       (
                           EXISTS (SELECT 1 FROM shipment_tracks WHERE shipment_id = s.id AND status = 'stuffing' AND tracked_at IS NOT NULL)
                           AND NOT (s.mode IN ('sea', 'sea_freight') AND s.vehicle_loading IN ('rack', 'flat_rack'))
                           AND NOT EXISTS (SELECT 1 FROM shipment_tracks WHERE shipment_id = s.id AND status = 'delivery_to_port' AND tracked_at IS NOT NULL)
                           AND (
                               EXISTS (
                                   SELECT 1 FROM units u WHERE u.shipment_id = s.id
                                   AND NOT EXISTS (
                                       SELECT 1 FROM unit_inspections ui
                                       WHERE ui.unit_id = u.id AND ui.stage = 'loading' AND ui.submitted_at IS NOT NULL
                                   )
                               )
                               OR EXISTS (
                                   SELECT 1 FROM units u JOIN unit_inspections ui ON ui.unit_id = u.id
                                   WHERE u.shipment_id = s.id AND ui.stage = 'loading' AND ui.gate_decision = 'return_to_pdc'
                               )
                           )
                       )
                       OR
                       -- Case 3: At Stacking (rack), loading session not completed
                       (
                           EXISTS (SELECT 1 FROM shipment_tracks WHERE shipment_id = s.id AND status = 'stacking' AND tracked_at IS NOT NULL)
                           AND s.mode IN ('sea', 'sea_freight') AND s.vehicle_loading IN ('rack', 'flat_rack')
                           AND NOT EXISTS (SELECT 1 FROM shipment_tracks WHERE shipment_id = s.id AND status = 'unit_loading' AND tracked_at IS NOT NULL)
                           AND NOT EXISTS (
                               SELECT 1 FROM loading_sessions ls
                               WHERE ls.shipment_id = s.id AND ls.operation_type = 'loading' AND ls.status = 'completed'
                           )
                       )
                   )
                ) AS tertahan
        ", [
            'depot_a' => $depotId,
            'depot_b' => $depotId,
        ]);

        return [
            'bermasalah' => (int) ($row?->bermasalah ?? 0),
            'tertahan'   => (int) ($row?->tertahan   ?? 0),
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

                -- C. Unit Loading Hari Ini (Stuffing non-rack ATAU UnitLoading rack, tracked today)
                (SELECT COUNT(u.id)
                 FROM units u
                 JOIN shipments s ON s.id = u.shipment_id
                 WHERE s.assigned_depot_id = :depot_c
                   AND (
                       -- Non-rack: Stuffing dicatat hari ini
                       (NOT (s.mode IN ('sea', 'sea_freight') AND s.vehicle_loading IN ('rack', 'flat_rack'))
                        AND EXISTS (
                            SELECT 1 FROM shipment_tracks
                            WHERE shipment_id = s.id AND status = 'stuffing' AND tracked_at::date = CURRENT_DATE
                        ))
                       OR
                       -- Rack: UnitLoading dicatat hari ini (AppSheet auto-advance)
                       ((s.mode IN ('sea', 'sea_freight') AND s.vehicle_loading IN ('rack', 'flat_rack'))
                        AND EXISTS (
                            SELECT 1 FROM shipment_tracks
                            WHERE shipment_id = s.id AND status = 'unit_loading' AND tracked_at::date = CURRENT_DATE
                        ))
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

}
