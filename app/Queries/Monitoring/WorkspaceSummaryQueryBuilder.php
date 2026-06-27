<?php

namespace App\Queries\Monitoring;

use App\DTO\Monitoring\MonitoringFilter;
use App\Enums\ShipmentStatus;
use App\Models\Branch;
use App\Models\Shipment;
use App\Support\Monitoring\RouteResolver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

final class WorkspaceSummaryQueryBuilder
{
    public function rawSummary(MonitoringFilter $filter): array
    {
        $cacheKey = 'monitoring:summary:' . $filter->cacheKey();
        $ttl = config('monitoring.cache_ttl', 30);

        return Cache::remember($cacheKey, now()->addSeconds($ttl), function () use ($filter) {
            $query = $this->build($filter);

            $counts = $query->selectRaw("
                SUM(CASE WHEN status NOT IN (?, ?) THEN 1 ELSE 0 END) AS active_units,
                SUM(CASE WHEN status IN (?, ?) THEN 1 ELSE 0 END) AS finished_units
            ", [
                ShipmentStatus::Delivered->value,
                ShipmentStatus::Cancelled->value,
                ShipmentStatus::Delivered->value,
                ShipmentStatus::Cancelled->value,
            ])->first();

            $activeUnits = (int) ($counts->active_units ?? 0);

            return [
                'active_units' => $activeUnits,
                'finished_units' => (int) ($counts->finished_units ?? 0),
                'route' => $filter->route ?? 'all',
                'branch' => $this->branchName($filter->branch_id),
                'refreshed_at' => Carbon::now(),
                'filtered_units' => $activeUnits,
            ];
        });
    }

    public function build(MonitoringFilter $filter): Builder
    {
        $query = Shipment::query();

        $query->whereNotIn('status', [ShipmentStatus::Draft->value]);

        if ($filter->branch_id) {
            $query->where('branch_id', $filter->branch_id);
        }

        if ($filter->mode) {
            $query->where('mode', $filter->mode);
        }

        $customerIds = RouteResolver::customerIdsForRoute($filter->route);
        if ($filter->route && $filter->route !== 'all' && !empty($customerIds)) {
            $query->whereIn('customer_id', $customerIds);
        }

        return $query;
    }

    private function branchName(?int $branchId): string
    {
        if (!$branchId) {
            return 'Semua Cabang';
        }

        return Branch::whereKey($branchId)->value('name') ?? '—';
    }
}