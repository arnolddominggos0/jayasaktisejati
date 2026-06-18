<?php

namespace App\Filament\FC\Pages\Dashboard;

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
     *   view_url       string (hanya jika has_briefing) — baca detail, bukan edit
     *   monitoring_url string — selalu ada, link ke Monitoring Operasional
     */
    public function getTodayBriefingStatus(): array
    {
        $session       = $this->getTodayBriefingSession();
        $monitoringUrl = MpReadinessMonitoring::getUrl();

        if (! $session) {
            return [
                'has_briefing'   => false,
                'monitoring_url' => $monitoringUrl,
            ];
        }

        $fitCount = $session->readyManpowerCount();

        return [
            'has_briefing'   => true,
            'fit_count'      => $fitCount,
            'need_mp'        => (int) ($session->summary_headcount ?? 0),
            'is_ready'       => (bool) $session->summary_sufficient,
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
                'label' => 'Belum Ada Briefing',
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
     *   mp_attend       int|null
     *   mp_need         int|null
     *   container_need  int|null
     *   container_avail int|null
     */
    public function getTodayOperationalReadiness(): array
    {
        // MP — dari briefing session hari ini
        $session    = $this->getTodayBriefingSession();
        $mpReady    = $session !== null ? (bool) $session->summary_sufficient : null;

        // Attend count — hanya load jika ada session
        $mpAttend = null;
        $mpNeed   = null;
        if ($session) {
            $mpAttend = (int) DB::table('briefing_attendances')
                ->where('session_id', $session->id)
                ->where('attendance_status', 'present')
                ->count();
            $mpNeed = (int) ($session->summary_headcount ?? 0);
        }

        // Container — satu data global per hari
        $cRow = DB::table('container_readiness_sessions')
            ->whereDate('session_date', Carbon::today())
            ->select('summary_sufficient', 'container_need', 'container_available')
            ->first();

        $containerReady = $cRow !== null ? (bool) $cRow->summary_sufficient : null;
        $containerNeed  = $cRow ? (int) $cRow->container_need      : null;
        $containerAvail = $cRow ? (int) $cRow->container_available : null;

        // Overall: false jika salah satu false; true jika keduanya true/tidak null; null jika keduanya null
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
            'mp_attend'       => $mpAttend,
            'mp_need'         => $mpNeed,
            'container_need'  => $containerNeed,
            'container_avail' => $containerAvail,
        ];
    }

    // ── Shipment Aktif Hari Ini ───────────────────────────────────────────────

    public function getTodayActiveShipments(): array
    {
        $depotId = $this->getDepotContext()?->id;
        if (! $depotId) return [];

        $exitExists = "(SELECT 1 FROM shipment_tracks st_exit
            WHERE st_exit.shipment_id = shipments.id
            AND st_exit.tracked_at IS NOT NULL
            AND (
                (shipments.mode IN ('sea', 'sea_freight') AND shipments.vehicle_loading IN ('rack', 'flat_rack') AND st_exit.status = 'delivery_to_port')
                OR (NOT (shipments.mode IN ('sea', 'sea_freight') AND shipments.vehicle_loading IN ('rack', 'flat_rack')) AND st_exit.status = 'stuffing')
            ))";

        $inYard = "EXISTS (SELECT 1 FROM shipment_tracks st_h WHERE st_h.shipment_id = shipments.id AND st_h.status = 'handover' AND st_h.tracked_at IS NOT NULL) AND NOT EXISTS {$exitExists}";

        $rows = DB::table('shipments')
            ->select([
                'shipments.code',
                'shipments.voyage',
                DB::raw("(SELECT COUNT(*) FROM units u WHERE u.shipment_id = shipments.id) AS total_units"),
                DB::raw("(SELECT COUNT(*) FROM units u WHERE u.shipment_id = shipments.id AND {$inYard}) AS in_yard"),
                DB::raw("(SELECT COUNT(*) FROM units u WHERE u.shipment_id = shipments.id AND {$inYard}
                    AND EXISTS (SELECT 1 FROM unit_inspections ui WHERE ui.unit_id = u.id AND ui.stage = 'handover_depot' AND ui.submitted_at IS NOT NULL AND ui.gate_decision IN ('accept', 'allow_with_remark'))
                ) AS ready"),
                DB::raw("(SELECT COUNT(*) FROM units u WHERE u.shipment_id = shipments.id AND {$inYard}
                    AND EXISTS (SELECT 1 FROM unit_inspections ui WHERE ui.unit_id = u.id AND ui.stage = 'handover_depot' AND ui.gate_decision = 'return_to_pdc')
                ) AS ng"),
                DB::raw("(SELECT COUNT(*) FROM units u WHERE u.shipment_id = shipments.id AND EXISTS {$exitExists}) AS loaded"),
            ])
            ->where('shipments.assigned_depot_id', $depotId)
            ->whereExists(fn ($q) => $q
                ->from('shipment_tracks as st_h')
                ->whereColumn('st_h.shipment_id', 'shipments.id')
                ->where('st_h.status', 'handover')
                ->whereNotNull('st_h.tracked_at')
            )
            ->orderBy('shipments.code')
            ->get();

        return $rows->map(function ($s) {
            $inYard  = (int) $s->in_yard;
            $ready   = (int) $s->ready;
            $ng      = (int) $s->ng;
            $loaded  = (int) $s->loaded;
            $waiting = max(0, $inYard - $ready - $ng);

            if ($loaded > 0) {
                $status = 'LOADING';
                $color  = 'info';
            } elseif ($ng > 0) {
                $status = 'NG';
                $color  = 'danger';
            } elseif ($inYard > 0 && $ready >= $inYard) {
                $status = 'READY';
                $color  = 'success';
            } else {
                $status = 'PARTIAL';
                $color  = 'warning';
            }

            return [
                'code'    => $s->code,
                'voyage'  => $s->voyage,
                'total'   => (int) $s->total_units,
                'in_yard' => $inYard,
                'ready'   => $ready,
                'ng'      => $ng,
                'loaded'  => $loaded,
                'waiting' => $waiting,
                'status'  => $status,
                'color'   => $color,
            ];
        })->toArray();
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
}
