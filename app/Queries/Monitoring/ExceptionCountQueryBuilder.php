<?php

namespace App\Queries\Monitoring;

use App\DTO\Monitoring\MonitoringFilter;
use App\Enums\ShipmentStatus;
use App\Models\Unit;
use App\Support\Monitoring\LatestTrackSubquery;
use App\Support\Monitoring\MonitoringDomain;
use App\Support\Monitoring\RouteResolver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Rooted on Unit: exception counts represent Units (vehicles) affected, not
 * Shipments (SPPB). e.g. "5 Hold" means 5 vehicles whose shipment is in Hold
 * — not 5 SPPBs.
 *
 * NG is now a direct check on unit_inspections (unit_id = units.id) instead of
 * a correlated subquery joining through units. All other exceptions read from
 * shipments.* or lt.* via the JOIN.
 */
final class ExceptionCountQueryBuilder
{
    public function rawCounts(MonitoringFilter $filter): array
    {
        $cacheKey = 'monitoring:exceptions:' . $filter->cacheKey();
        $ttl = config('monitoring.cache_ttl', 30);

        return Cache::remember($cacheKey, now()->addSeconds($ttl), function () use ($filter) {
            return $this->aggregate($filter);
        });
    }

    /**
     * Single aggregate query, no correlated subqueries.
     *
     * NG check: LEFT JOIN to a pre-aggregated set of unit_ids with failed
     * inspections so the planner can hash-join once.
     *
     * @return array{delay:int, hold:int, ng:int, demurrage:int, missing_voyage:int, stuck:int}
     */
    private function aggregate(MonitoringFilter $filter): array
    {
        $stuckDays     = (int) config('monitoring.stuck_days', 3);
        $demurrageDays = (int) config('monitoring.demurrage_days', 7);

        $portStatuses = implode("','", ['stacking', 'delivery_to_port', 'vessel_arrival', 'unloading']);

        // Latest track per shipment — keyed by shipment_id as before.
        $latestTrackSub = LatestTrackSubquery::build(
            statusAlias: 'latest_status',
            trackedAtAlias: 'latest_tracked_at',
        );

        // Pre-aggregate the failed unit_ids so the planner can hash-join once.
        $ngUnitsSub = DB::table('unit_inspections as ui')
            ->select('ui.unit_id')
            ->where('ui.status', 'failed')
            ->distinct();

        $result = $this->build($filter)
            ->leftJoinSub($latestTrackSub, 'lt', 'lt.shipment_id', '=', 'units.shipment_id')
            ->leftJoinSub($ngUnitsSub, 'ng', 'ng.unit_id', '=', 'units.id')
            ->selectRaw("
                COUNT(*) FILTER (WHERE
                    shipments.eta IS NOT NULL
                    AND shipments.eta < NOW()
                    AND shipments.status NOT IN ('hold', 'delivered', 'cancelled')
                ) AS delay,

                COUNT(*) FILTER (WHERE shipments.status = 'hold') AS hold,

                COUNT(*) FILTER (WHERE ng.unit_id IS NOT NULL) AS ng,

                COUNT(*) FILTER (WHERE
                    lt.latest_status IN ('{$portStatuses}')
                    AND lt.latest_tracked_at IS NOT NULL
                    AND lt.latest_tracked_at < NOW() - INTERVAL '{$demurrageDays} days'
                ) AS demurrage,

                COUNT(*) FILTER (WHERE
                    shipments.mode = 'sea'
                    AND shipments.voyage_id IS NULL
                    AND shipments.status NOT IN ('delivered', 'cancelled')
                ) AS missing_voyage,

                COUNT(*) FILTER (WHERE
                    COALESCE(lt.latest_tracked_at, shipments.requested_at, shipments.created_at)
                        < NOW() - INTERVAL '{$stuckDays} days'
                    AND shipments.status NOT IN ('delivered', 'cancelled')
                ) AS stuck
            ")
            ->first();

        return [
            'delay'          => (int) ($result?->delay ?? 0),
            'hold'           => (int) ($result?->hold ?? 0),
            'ng'             => (int) ($result?->ng ?? 0),
            'demurrage'      => (int) ($result?->demurrage ?? 0),
            'missing_voyage' => (int) ($result?->missing_voyage ?? 0),
            'stuck'          => (int) ($result?->stuck ?? 0),
        ];
    }

    public function build(MonitoringFilter $filter): Builder
    {
        $query = Unit::query()
            ->join('shipments', 'shipments.id', '=', 'units.shipment_id');

        $query->whereNotIn('shipments.status', [ShipmentStatus::Draft->value]);

        // Domain constraint: sea mode only.
        MonitoringDomain::applyTo($query);

        if ($filter->branch_id) {
            $query->where('shipments.branch_id', $filter->branch_id);
        }

        \App\Support\Monitoring\PeriodResolver::applyTo($query, $filter->period);

        $customerIds = RouteResolver::customerIdsForRoute($filter->route);
        if ($filter->route && $filter->route !== 'all' && ! empty($customerIds)) {
            $query->whereIn('shipments.customer_id', $customerIds);
        }

        $finishedStatuses = [ShipmentStatus::Delivered->value, ShipmentStatus::Cancelled->value];
        match ($filter->status) {
            'finished' => $query->whereIn('shipments.status', $finishedStatuses),
            'all'      => null,
            default    => $query->whereNotIn('shipments.status', $finishedStatuses),
        };

        return $query;
    }
}
