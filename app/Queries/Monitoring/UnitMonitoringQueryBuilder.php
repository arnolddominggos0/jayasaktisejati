<?php

namespace App\Queries\Monitoring;

use App\DTO\Monitoring\MonitoringFilter;
use App\Enums\ShipmentStatus;
use App\Models\Unit;
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
     * Rooted on Unit. All Shipment fields are accessed via the JOIN to
     * shipments on units.shipment_id. Filter pipeline and
     * sort expressions are unchanged — they still reference the same column names
     * via the joined shipments table.
     *
     * Pipeline: Unit JOIN shipments → Branch → Period → Route → Status
     * → [JOIN latestTrack] → Computed columns → Exception → Search → Sort → Eager load.
     */
    public function build(MonitoringFilter $filter): Builder
    {
        $query = Unit::query();

        $this->joinShipment($query);
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

    // ── Core JOIN ─────────────────────────────────────────────────────────────

    /**
     * The foundational JOIN that turns Unit into the root and exposes all
     * Shipment columns for subsequent filters and computed columns.
     */
    private function joinShipment(Builder $q): void
    {
        $q->join('shipments', 'shipments.id', '=', 'units.shipment_id');
    }

    // ── Scope filters ─────────────────────────────────────────────────────────

    private function applyBaseScope(Builder $q): void
    {
        $q->whereNotIn('shipments.status', [ShipmentStatus::Draft->value]);
    }

    /**
     * Hard-pins the domain constraint: sea mode only.
     * MonitoringDomain::applyTo() targets shipments.mode, which is available
     * via the JOIN.
     */
    private function applyModeFilter(Builder $q): void
    {
        MonitoringDomain::applyTo($q);
    }

    private function applyBranch(Builder $q, MonitoringFilter $f): void
    {
        if ($f->branch_id) {
            $q->where('shipments.branch_id', $f->branch_id);
        }
    }

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

    private function applyStatusFilter(Builder $q, MonitoringFilter $f): void
    {
        $finishedStatuses = [ShipmentStatus::Delivered->value, ShipmentStatus::Cancelled->value];

        match ($f->status) {
            'finished' => $q->whereIn('shipments.status', $finishedStatuses),
            'all'      => null,
            default    => $q->whereNotIn('shipments.status', $finishedStatuses),
        };
    }

    // ── Latest-track JOIN ─────────────────────────────────────────────────────

    /**
     * LEFT JOIN to the latest track per shipment. The subquery still keys on
     * shipment_id — units within the same shipment share the same track position.
     */
    private function joinLatestTrack(Builder $q): void
    {
        $q->leftJoinSub(
            LatestTrackSubquery::build(statusAlias: 'lt_status', trackedAtAlias: 'lt_tracked_at'),
            'lt',
            'lt.shipment_id',
            '=',
            'units.shipment_id',
        );
    }

    // ── Computed columns ──────────────────────────────────────────────────────

    /**
     * SELECT units.* only — Shipment data comes via the eager-loaded
     * $unit->shipment relation. Selecting shipments.* here would collide on
     * id/created_at/updated_at.
     *
     * has_ng_inspection: direct check on this unit's inspections — no correlated
     * subquery needed since we're already on a unit row.
     */
    private function applyComputedColumns(Builder $q): void
    {
        $q->select('units.*');

        // NG indicator: does this specific unit have any failed inspection?
        $q->addSelect(DB::raw("
            EXISTS(
                SELECT 1
                FROM   unit_inspections ui
                WHERE  ui.unit_id = units.id
                AND    ui.status = 'failed'
            ) AS has_ng_inspection
        "));
    }

    // ── Exception filter ──────────────────────────────────────────────────────

    /**
     * Narrow the result set to units whose shipment has the specified active
     * exception. All thresholds come from config — no hardcoding.
     * Conditions reference shipments.* via the JOIN.
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
                    ->from('unit_inspections as ui')
                    ->whereColumn('ui.unit_id', 'units.id')
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
     * Full operational-field search.
     * Unit-level: reg_no, chassis_no, engine_no, sjkb_no, container_display — now
     * direct WHERE conditions (no subquery needed — units is the root table).
     * Shipment-level: code, doc_number, voyage, vessel_name, container_no — via JOIN.
     * Customer-level: name via whereExists (customer isn't joined for WHERE performance).
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
                // Unit fields — direct (root table)
                ->where('units.reg_no', 'ilike', $term)
                ->orWhere('units.chassis_no', 'ilike', $term)
                ->orWhere('units.engine_no', 'ilike', $term)
                ->orWhere('units.sjkb_no', 'ilike', $term)
                ->orWhere('units.container_display', 'ilike', $term)
                // Shipment fields — via JOIN
                ->orWhere('shipments.code', 'ilike', $term)
                ->orWhere('shipments.doc_number', 'ilike', $term)
                ->orWhere('shipments.voyage', 'ilike', $term)
                ->orWhere('shipments.vessel_name', 'ilike', $term)
                ->orWhere('shipments.container_no', 'ilike', $term)
                // Customer — via EXISTS (not joined for WHERE; too expensive for all rows)
                ->orWhereExists(function ($sub) use ($term) {
                    $sub->select(DB::raw(1))
                        ->from('customers')
                        ->whereColumn('customers.id', 'shipments.customer_id')
                        ->where('customers.name', 'ilike', $term);
                });
        });

        $q->addSelect(DB::raw('true AS is_search_match'));
    }

    // ── Sort ──────────────────────────────────────────────────────────────────

    /**
     * Sort expressions are unchanged — they still reference shipments.* (via JOIN)
     * and lt.* (via leftJoinSub). The only difference: age fallback uses
     * shipments.requested_at / shipments.created_at, which are still available.
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
                        SELECT 1 FROM unit_inspections ui
                        WHERE ui.unit_id = units.id AND ui.status = 'failed'
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

    private function applyCustomerSort(Builder $q, string $sort): void
    {
        $q->leftJoin('customers', 'customers.id', '=', 'shipments.customer_id');

        $direction = $sort === 'customer-desc' ? 'DESC' : 'ASC';
        $q->orderByRaw("customers.name {$direction} NULLS LAST");
    }

    // ── Eager loading ─────────────────────────────────────────────────────────

    /**
     * Eager-load Shipment relations consumed by MonitoringRowBuilder.
     * The shipment itself is eager-loaded so MonitoringRowBuilder can access
     * $unit->shipment without triggering additional queries.
     * latestTrack, customer, branch, originCity, destinationCity are loaded
     * via the shipment relation to preserve the same N=0-extra-query guarantee
     * as the old Shipment-root query.
     */
    private function applyEagerLoading(Builder $q): void
    {
        $q->with([
            'shipment.latestTrack:shipment_tracks.id,shipment_tracks.shipment_id,shipment_tracks.status,shipment_tracks.tracked_at',
            'shipment.customer:id,name',
            'shipment.branch:id,name',
            'shipment.originCity:id,name',
            'shipment.destinationCity:id,name',
        ]);
    }
}
