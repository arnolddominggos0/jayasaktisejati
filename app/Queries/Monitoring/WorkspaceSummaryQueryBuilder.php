<?php

namespace App\Queries\Monitoring;

use App\DTO\Monitoring\MonitoringFilter;
use App\Enums\ShipmentStatus;
use App\Models\Branch;
use App\Models\Unit;
use App\Support\Monitoring\MonitoringDomain;
use App\Support\Monitoring\PeriodResolver;
use App\Support\Monitoring\RouteResolver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Rooted on Unit: KPI counts represent Units (vehicles), not Shipments (SPPB).
 * All filter conditions still reference shipments.* via the JOIN.
 */
final class WorkspaceSummaryQueryBuilder
{
    public function rawSummary(MonitoringFilter $filter): array
    {
        $cacheKey = 'monitoring:summary:' . $filter->cacheKey();
        $ttl = config('monitoring.cache_ttl', 30);

        return Cache::remember($cacheKey, now()->addSeconds($ttl), function () use ($filter) {
            $query = $this->build($filter);

            $counts = $query->selectRaw("
                SUM(CASE WHEN shipments.status NOT IN (?, ?) THEN 1 ELSE 0 END) AS active_units,
                SUM(CASE WHEN shipments.status IN (?, ?) THEN 1 ELSE 0 END) AS finished_units
            ", [
                ShipmentStatus::Delivered->value,
                ShipmentStatus::Cancelled->value,
                ShipmentStatus::Delivered->value,
                ShipmentStatus::Cancelled->value,
            ])->first();

            $activeUnits = (int) ($counts->active_units ?? 0);

            return [
                'active_units'   => $activeUnits,
                'finished_units' => (int) ($counts->finished_units ?? 0),
                'route'          => $filter->route ?? 'all',
                'branch'         => $this->branchName($filter->branch_id),
                'refreshed_at'   => Carbon::now(),
                'filtered_units' => $activeUnits,
                'period'         => $filter->period,
            ];
        });
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

        PeriodResolver::applyTo($query, $filter->period);

        $customerIds = RouteResolver::customerIdsForRoute($filter->route);
        if ($filter->route && $filter->route !== 'all' && ! empty($customerIds)) {
            $query->whereIn('shipments.customer_id', $customerIds);
        }

        return $query;
    }

    private function branchName(?int $branchId): string
    {
        if (! $branchId) {
            return 'Semua Cabang';
        }

        return Branch::whereKey($branchId)->value('name') ?? '—';
    }
}
