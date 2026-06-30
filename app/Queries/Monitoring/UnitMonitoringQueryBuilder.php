<?php

namespace App\Queries\Monitoring;

use App\DTO\Monitoring\MonitoringFilter;
use App\Enums\ShipmentStatus;
use App\Models\Shipment;
use App\Support\Monitoring\LatestTrackSubquery;
use App\Support\Monitoring\MonitoringDomain;
use App\Support\Monitoring\PeriodResolver;
use App\Support\Monitoring\RouteResolver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

final class UnitMonitoringQueryBuilder
{
    /**
     * SQL CASE mirroring TrackStatus::toNormalizedValue() — static enum metadata,
     * not Stage Engine business logic. Used only to make stage/progress sorting
     * numerically meaningful instead of alphabetical-by-string-value.
     */
    private const STAGE_ORDER_CASE = "
        CASE lt.lt_status
            WHEN 'pickup' THEN 10
            WHEN 'handover' THEN 20
            WHEN 'stuffing' THEN 30
            WHEN 'delivery_to_port' THEN 40
            WHEN 'stacking' THEN 50
            WHEN 'unit_loading' THEN 60
            WHEN 'onship' THEN 70
            WHEN 'vessel_depart' THEN 80
            WHEN 'vessel_arrival' THEN 90
            WHEN 'unloading' THEN 100
            WHEN 'handover_trucking' THEN 105
            WHEN 'delivery_to_customer' THEN 110
            WHEN 'delivered' THEN 120
            WHEN 'hold' THEN 900
            WHEN 'cancelled' THEN 999
            ELSE 10
        END
    ";

    /**
     * Sprint 6.4.2: Branch and Period moved to the front of the pipeline —
     * they're the workspace's primary context, typically the most selective
     * filters, and don't need the lt JOIN, so applying them first keeps the
     * query efficient. Pipeline: Branch → Period → Route → Status → [JOIN]
     * → Exception → Search → Sort.
     */
    public function build(MonitoringFilter $filter): Builder
    {
        $query = Shipment::query();

        $this->applyBaseScope($query);
        $this->applyModeFilter($query);
        $this->applyBranch($query, $filter);
        $this->applyPeriod($query, $filter);
        $this->applyRouteFilter($query, $filter);
        $this->applyStatusFilter($query, $filter);
        // JOIN must precede any clause that references lt.* (exception filter, sort)
        $this->joinLatestTrack($query);
        $this->applyComputedColumns($query);
        $this->applyExceptionFilter($query, $filter);
        $this->applySearchMatch($query, $filter);
        $this->applySort($query, $filter);
        $this->applyEagerLoading($query);

        return $query;
    }

    // ── Scope filters ─────────────────────────────────────────────────────────

    private function applyBaseScope(Builder $q): void
    {
        // Table-qualified to prevent ambiguity after the lt LEFT JOIN
        $q->whereNotIn('shipments.status', [ShipmentStatus::Draft->value]);
    }

    /**
     * Hard-pins the v1 domain constraint: sea mode only.
     * See ADR-009 — this is a domain gate, not a user-facing filter, so it
     * runs before Branch/Period regardless of how those are configured.
     */
    private function applyModeFilter(Builder $q): void
    {
        MonitoringDomain::applyTo($q);
    }

    /** Sprint 6.4.2: workspace branch context. */
    private function applyBranch(Builder $q, MonitoringFilter $f): void
    {
        if ($f->branch_id) {
            $q->where('shipments.branch_id', $f->branch_id);
        }
    }

    /**
     * Sprint 6.4.2: workspace period context — restricts to the calendar
     * month identified by $f->period. See PeriodResolver::applyTo() — shared
     * with ExceptionCountQueryBuilder/WorkspaceSummaryQueryBuilder so the
     * table and the header KPIs can never disagree about "this period".
     */
    private function applyPeriod(Builder $q, MonitoringFilter $f): void
    {
        PeriodResolver::applyTo($q, $f->period);
    }

    private function applyRouteFilter(Builder $q, MonitoringFilter $f): void
    {
        $customerIds = RouteResolver::customerIdsForRoute($f->route);
        if ($f->route && $f->route !== 'all' && ! empty($customerIds)) {
            $q->whereIn('shipments.customer_id', $customerIds);
        }
    }

    /**
     * Sprint 6.4.1: three-state status filter (was a boolean show_finished).
     * 'active'   — hide delivered/cancelled (default)
     * 'finished' — delivered/cancelled only
     * 'all'      — no restriction
     */
    private function applyStatusFilter(Builder $q, MonitoringFilter $f): void
    {
        $finishedStatuses = [ShipmentStatus::Delivered->value, ShipmentStatus::Cancelled->value];

        match ($f->status) {
            'finished' => $q->whereIn('shipments.status', $finishedStatuses),
            'all'      => null,
            default    => $q->whereNotIn('shipments.status', $finishedStatuses), // 'active'
        };
    }

    // ── Latest-track JOIN ─────────────────────────────────────────────────────

    /**
     * LEFT JOIN to a DISTINCT ON subquery that returns one row per shipment —
     * the most-recent track by id. Uses the shared LatestTrackSubquery helper
     * so this pattern is not duplicated across query builders.
     */
    private function joinLatestTrack(Builder $q): void
    {
        $q->leftJoinSub(
            LatestTrackSubquery::build(statusAlias: 'lt_status', trackedAtAlias: 'lt_tracked_at'),
            'lt',
            'lt.shipment_id',
            '=',
            'shipments.id',
        );
    }

    // ── Computed columns ──────────────────────────────────────────────────────

    /**
     * Replace the default SELECT * with an explicit shipments.* plus computed
     * columns used by MonitoringRowBuilder (has_ng_inspection, is_search_match).
     * Explicit selection avoids column-name collisions from the lt JOIN.
     */
    private function applyComputedColumns(Builder $q): void
    {
        $q->select('shipments.*');

        // NG indicator: does any unit inspection for this shipment have status='failed'?
        $q->addSelect(DB::raw("
            EXISTS(
                SELECT 1
                FROM   units u
                JOIN   unit_inspections ui ON ui.unit_id = u.id
                WHERE  u.shipment_id = shipments.id
                AND    ui.status = 'failed'
            ) AS has_ng_inspection
        "));
    }

    // ── Exception filter ──────────────────────────────────────────────────────

    /**
     * Narrow the result set to shipments that have the specified active exception.
     * All thresholds come from config — no hardcoding.
     */
    private function applyExceptionFilter(Builder $q, MonitoringFilter $f): void
    {
        if (! $f->exception_filter) {
            return;
        }

        $demurrageDays = (int) config('monitoring.demurrage_days', 7);
        $stuckDays     = (int) config('monitoring.stuck_days', 3);
        $portStatuses  = ['stacking', 'delivery_to_port', 'vessel_arrival', 'unloading'];
        $finishedStatuses = [ShipmentStatus::Delivered->value, ShipmentStatus::Cancelled->value];

        match ($f->exception_filter) {
            'hold' => $q->where('shipments.status', ShipmentStatus::Hold->value),

            'ng' => $q->whereExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('units as u')
                    ->join('unit_inspections as ui', 'ui.unit_id', '=', 'u.id')
                    ->whereColumn('u.shipment_id', 'shipments.id')
                    ->where('ui.status', 'failed');
            }),

            'delay' => $q
                ->whereNotNull('shipments.eta')
                ->where('shipments.eta', '<', now())
                ->whereNotIn('shipments.status', [
                    ShipmentStatus::Hold->value,
                    ...$finishedStatuses,
                ]),

            'demurrage' => $q
                ->whereIn('lt.lt_status', $portStatuses)
                ->whereNotNull('lt.lt_tracked_at')
                ->whereRaw("lt.lt_tracked_at < NOW() - INTERVAL '{$demurrageDays} days'"),

            'missing_voyage' => $q
                ->where('shipments.mode', 'sea')
                ->whereNull('shipments.voyage_id')
                ->whereNotIn('shipments.status', $finishedStatuses),

            'stuck' => $q
                ->whereRaw(
                    "COALESCE(lt.lt_tracked_at, shipments.requested_at, shipments.created_at)"
                    . " < NOW() - INTERVAL '{$stuckDays} days'"
                )
                ->whereNotIn('shipments.status', $finishedStatuses),

            default => null,
        };
    }

    // ── Search ────────────────────────────────────────────────────────────────

    /**
     * Sprint 6.4.1: full operational-field search.
     * Shipment-level: code, doc_number (SPPB), voyage no, vessel name, container no.
     * Unit-level: reg_no (no polisi), chassis_no, engine_no, sjkb_no, container_display.
     * Customer-level: customer name (via whereExists — customer isn't joined for WHERE).
     * Adds is_search_match computed column so the Blade can highlight matched rows.
     * When no search term, is_search_match is always false.
     */
    private function applySearchMatch(Builder $q, MonitoringFilter $f): void
    {
        if (blank($f->search)) {
            $q->addSelect(DB::raw('false AS is_search_match'));

            return;
        }

        $term = '%' . $f->search . '%';

        $q->where(function (Builder $where) use ($term) {
            $where
                ->where('shipments.code', 'ilike', $term)
                ->orWhere('shipments.doc_number', 'ilike', $term)
                ->orWhere('shipments.voyage', 'ilike', $term)
                ->orWhere('shipments.vessel_name', 'ilike', $term)
                ->orWhere('shipments.container_no', 'ilike', $term)
                ->orWhereExists(function ($sub) use ($term) {
                    $sub->select(DB::raw(1))
                        ->from('units')
                        ->whereColumn('units.shipment_id', 'shipments.id')
                        ->where(function ($q2) use ($term) {
                            $q2->where('units.reg_no', 'ilike', $term)
                               ->orWhere('units.chassis_no', 'ilike', $term)
                               ->orWhere('units.engine_no', 'ilike', $term)
                               ->orWhere('units.sjkb_no', 'ilike', $term)
                               ->orWhere('units.container_display', 'ilike', $term);
                        });
                })
                ->orWhereExists(function ($sub) use ($term) {
                    $sub->select(DB::raw(1))
                        ->from('customers')
                        ->whereColumn('customers.id', 'shipments.customer_id')
                        ->where('customers.name', 'ilike', $term);
                });
        });

        $q->addSelect(DB::raw('true AS is_search_match'));
    }

    // ── Sort ─────────────────────────────────────────────────────────────────

    /**
     * Default sort (exception-first): Hold → NG → Demurrage → Delay → Stuck →
     * Missing Voyage → Age DESC.
     * All thresholds come from config; integer interpolation is safe (no user input).
     *
     * Sprint 6.4.1 additions: progress, eta, voyage, customer sorts; stage sort
     * fixed to use TrackStatus's numeric order instead of alphabetical string
     * order (STAGE_ORDER_CASE — static enum metadata, not Stage Engine logic).
     * Progress sort reuses the same stage scale since progress is a monotonic
     * function of stage for the 10–120 range; cancelled/hold collapse to 0 to
     * match ProgressCalculator's displayed percentage, not duplicated here
     * beyond ranking order.
     */
    private function applySort(Builder $q, MonitoringFilter $f): void
    {
        $demurrageDays = (int) config('monitoring.demurrage_days', 7);
        $stuckDays     = (int) config('monitoring.stuck_days', 3);
        $portStatuses  = "'stacking','delivery_to_port','vessel_arrival','unloading'";

        if ($f->sort === 'exception-first' || $f->sort === '') {
            $q->orderByRaw("
                CASE
                    WHEN shipments.status = 'hold' THEN 1
                    WHEN EXISTS(
                        SELECT 1 FROM units u
                        JOIN unit_inspections ui ON ui.unit_id = u.id
                        WHERE u.shipment_id = shipments.id AND ui.status = 'failed'
                    ) THEN 2
                    WHEN (
                        lt.lt_status IN ({$portStatuses})
                        AND lt.lt_tracked_at IS NOT NULL
                        AND lt.lt_tracked_at < NOW() - INTERVAL '{$demurrageDays} days'
                    ) THEN 3
                    WHEN (
                        shipments.eta IS NOT NULL
                        AND shipments.eta < NOW()
                        AND shipments.status NOT IN ('hold', 'delivered', 'cancelled')
                    ) THEN 4
                    WHEN (
                        COALESCE(lt.lt_tracked_at, shipments.requested_at, shipments.created_at)
                            < NOW() - INTERVAL '{$stuckDays} days'
                        AND shipments.status NOT IN ('delivered', 'cancelled')
                    ) THEN 5
                    WHEN (
                        shipments.mode = 'sea'
                        AND shipments.voyage_id IS NULL
                        AND shipments.status NOT IN ('delivered', 'cancelled')
                    ) THEN 6
                    ELSE 7
                END ASC,
                COALESCE(shipments.requested_at, shipments.created_at) DESC
            ");

            return;
        }

        $ageFallback = 'COALESCE(shipments.requested_at, shipments.created_at)';
        $stageOrder  = self::STAGE_ORDER_CASE;
        // Stage-derived progress: cancelled/hold rank as 0 (matches ProgressCalculator's
        // displayed 0%); everything else uses the same 10–120 stage scale (monotonic
        // with the displayed percentage, so ranking order is identical).
        $progressOrder = "
            CASE
                WHEN shipments.status = 'cancelled' THEN 0
                WHEN lt.lt_status = 'hold' THEN 0
                ELSE {$stageOrder}
            END
        ";

        match ($f->sort) {
            'age-desc'      => $q->orderByRaw("{$ageFallback} DESC"),
            'age-asc'       => $q->orderByRaw("{$ageFallback} ASC"),

            'progress-desc' => $q->orderByRaw("{$progressOrder} DESC, {$ageFallback} DESC"),
            'progress-asc'  => $q->orderByRaw("{$progressOrder} ASC, {$ageFallback} DESC"),

            'eta-asc'       => $q->orderByRaw('shipments.eta ASC NULLS LAST'),
            'eta-desc'      => $q->orderByRaw('shipments.eta DESC NULLS LAST'),

            'voyage-asc'    => $q->orderByRaw('shipments.voyage ASC NULLS LAST'),
            'voyage-desc'   => $q->orderByRaw('shipments.voyage DESC NULLS LAST'),

            'customer-asc', 'customer-desc' => $this->applyCustomerSort($q, $f->sort),

            'stage-asc'     => $q->orderByRaw("{$stageOrder} ASC, {$ageFallback} DESC"),
            'stage-desc'    => $q->orderByRaw("{$stageOrder} DESC, {$ageFallback} DESC"),

            default         => $q->orderByRaw("{$ageFallback} DESC"),
        };
    }

    /**
     * Customer sort needs a real JOIN (ORDER BY can't reach into a WHERE-only
     * subquery), added only when this sort is actually requested.
     */
    private function applyCustomerSort(Builder $q, string $sort): void
    {
        $q->leftJoin('customers', 'customers.id', '=', 'shipments.customer_id');

        $direction = $sort === 'customer-desc' ? 'DESC' : 'ASC';
        $q->orderByRaw("customers.name {$direction} NULLS LAST");
    }

    // ── Eager loading ─────────────────────────────────────────────────────────

    /**
     * Minimum eager loading with column selection — avoids N+1 while keeping
     * data transfer small. All relations consumed by MonitoringRowBuilder.
     */
    private function applyEagerLoading(Builder $q): void
    {
        $q->with([
            'latestTrack:shipment_tracks.id,shipment_tracks.shipment_id,shipment_tracks.status,shipment_tracks.tracked_at',
            'customer:id,name',
            'branch:id,name',
            'originCity:id,name',
            'destinationCity:id,name',
            'units' => fn ($uq) => $uq->select([
                'id',
                'shipment_id',
                'reg_no',
                'model_no',
                'chassis_no',
                'color',
                'container_display',
            ]),
        ]);
    }
}
