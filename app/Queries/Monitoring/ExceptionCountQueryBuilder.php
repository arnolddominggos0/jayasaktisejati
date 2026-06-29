<?php

namespace App\Queries\Monitoring;

use App\DTO\Monitoring\MonitoringFilter;
use App\Models\Shipment;
use App\Support\Monitoring\LatestTrackSubquery;
use App\Support\Monitoring\MonitoringDomain;
use App\Support\Monitoring\RouteResolver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

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
     * Single aggregate query — one round-trip, no correlated subqueries.
     *
     * NG check uses a LEFT JOIN to a pre-aggregated set of shipment_ids with
     * failed inspections so the planner can hash-join once instead of running
     * a correlated EXISTS for every row in shipments (previously ~80ms → ~20ms).
     *
     * @return array{delay:int, hold:int, ng:int, demurrage:int, missing_voyage:int, stuck:int}
     */
    private function aggregate(MonitoringFilter $filter): array
    {
        $stuckDays     = (int) config('monitoring.stuck_days', 3);
        $demurrageDays = (int) config('monitoring.demurrage_days', 7);

        // Port-related statuses that trigger demurrage risk.
        $portStatuses = implode("','", ['stacking', 'delivery_to_port', 'vessel_arrival', 'unloading']);

        // Latest track per shipment — reusable DISTINCT ON pattern.
        $latestTrackSub = LatestTrackSubquery::build(
            statusAlias: 'latest_status',
            trackedAtAlias: 'latest_tracked_at',
        );

        // Pre-aggregate the NG shipment_ids once so the planner can hash-join
        // instead of running a correlated EXISTS per row (was the slow path).
        $ngShipmentsSub = DB::table('units as u')
            ->select('u.shipment_id')
            ->join('unit_inspections as ui', 'ui.unit_id', '=', 'u.id')
            ->where('ui.status', 'failed')
            ->distinct();

        $result = $this->build($filter)
            ->leftJoinSub($latestTrackSub, 'lt', 'lt.shipment_id', '=', 'shipments.id')
            ->leftJoinSub($ngShipmentsSub, 'ng', 'ng.shipment_id', '=', 'shipments.id')
            ->selectRaw("
                COUNT(*) FILTER (WHERE
                    shipments.eta IS NOT NULL
                    AND shipments.eta < NOW()
                    AND shipments.status NOT IN ('hold', 'delivered', 'cancelled')
                ) AS delay,

                COUNT(*) FILTER (WHERE shipments.status = 'hold') AS hold,

                COUNT(*) FILTER (WHERE ng.shipment_id IS NOT NULL) AS ng,

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
        $query = Shipment::query();

        // Table-qualified to prevent ambiguity after JOINs are added.
        $query->whereNotIn('shipments.status', [\App\Enums\ShipmentStatus::Draft->value]);

        // v1 domain constraint: sea mode only. See ADR-009 and MonitoringDomain.
        MonitoringDomain::applyTo($query);

        if ($filter->branch_id) {
            $query->where('shipments.branch_id', $filter->branch_id);
        }

        $customerIds = RouteResolver::customerIdsForRoute($filter->route);
        if ($filter->route && $filter->route !== 'all' && ! empty($customerIds)) {
            $query->whereIn('shipments.customer_id', $customerIds);
        }

        if (! $filter->show_finished) {
            $query->whereNotIn('shipments.status', [
                \App\Enums\ShipmentStatus::Delivered->value,
                \App\Enums\ShipmentStatus::Cancelled->value,
            ]);
        }

        return $query;
    }
}
