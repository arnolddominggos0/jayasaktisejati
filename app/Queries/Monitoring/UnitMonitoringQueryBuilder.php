<?php

namespace App\Queries\Monitoring;

use App\DTO\Monitoring\MonitoringFilter;
use App\Enums\ShipmentStatus;
use App\Models\Shipment;
use App\Support\Monitoring\LatestTrackSubquery;
use App\Support\Monitoring\MonitoringDomain;
use App\Support\Monitoring\RouteResolver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

final class UnitMonitoringQueryBuilder
{
    public function build(MonitoringFilter $filter): Builder
    {
        $query = Shipment::query();

        $this->applyBaseScope($query, $filter);
        $this->applyModeFilter($query, $filter);
        $this->applyRouteFilter($query, $filter);
        $this->applyShowFinished($query, $filter);
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

    private function applyBaseScope(Builder $q, MonitoringFilter $f): void
    {
        // Table-qualified to prevent ambiguity after the lt LEFT JOIN
        $q->whereNotIn('shipments.status', [ShipmentStatus::Draft->value]);

        if ($f->branch_id) {
            $q->where('shipments.branch_id', $f->branch_id);
        }
    }

    /**
     * Hard-pins the v1 domain constraint: sea mode only.
     * The $f parameter is retained for future v2 extension (see ADR-009).
     */
    private function applyModeFilter(Builder $q, MonitoringFilter $f): void
    {
        MonitoringDomain::applyTo($q);
    }

    private function applyRouteFilter(Builder $q, MonitoringFilter $f): void
    {
        $customerIds = RouteResolver::customerIdsForRoute($f->route);
        if ($f->route && $f->route !== 'all' && ! empty($customerIds)) {
            $q->whereIn('shipments.customer_id', $customerIds);
        }
    }

    private function applyShowFinished(Builder $q, MonitoringFilter $f): void
    {
        if (! $f->show_finished) {
            $q->whereNotIn('shipments.status', [
                ShipmentStatus::Delivered->value,
                ShipmentStatus::Cancelled->value,
            ]);
        }
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
     * Filter rows by search term (SPPB code, doc number, voyage snapshot, unit reg_no / chassis_no).
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
                ->orWhereExists(function ($sub) use ($term) {
                    $sub->select(DB::raw(1))
                        ->from('units')
                        ->whereColumn('units.shipment_id', 'shipments.id')
                        ->where(function ($q2) use ($term) {
                            $q2->where('units.reg_no', 'ilike', $term)
                               ->orWhere('units.chassis_no', 'ilike', $term);
                        });
                });
        });

        $q->addSelect(DB::raw('true AS is_search_match'));
    }

    // ── Sort ─────────────────────────────────────────────────────────────────

    /**
     * Default sort (exception-first): Hold → NG → Demurrage → Delay → Stuck →
     * Missing Voyage → Age DESC.
     * All thresholds come from config; integer interpolation is safe (no user input).
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

        match ($f->sort) {
            'age-desc'   => $q->orderByRaw(
                'COALESCE(shipments.requested_at, shipments.created_at) DESC'
            ),
            'age-asc'    => $q->orderByRaw(
                'COALESCE(shipments.requested_at, shipments.created_at) ASC'
            ),
            'stage-asc'  => $q->orderByRaw(
                "COALESCE(lt.lt_status, 'pickup') ASC, "
                . 'COALESCE(shipments.requested_at, shipments.created_at) DESC'
            ),
            'stage-desc' => $q->orderByRaw(
                "COALESCE(lt.lt_status, 'pickup') DESC, "
                . 'COALESCE(shipments.requested_at, shipments.created_at) DESC'
            ),
            default      => $q->orderByRaw(
                'COALESCE(shipments.requested_at, shipments.created_at) DESC'
            ),
        };
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
