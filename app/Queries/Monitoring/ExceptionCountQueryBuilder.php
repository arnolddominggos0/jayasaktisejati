<?php

namespace App\Queries\Monitoring;

use App\DTO\Monitoring\MonitoringFilter;
use App\Models\Shipment;
use App\Support\Monitoring\RouteResolver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

final class ExceptionCountQueryBuilder
{
    public function rawCounts(MonitoringFilter $filter): array
    {
        $cacheKey = 'monitoring:exceptions:' . $filter->cacheKey();
        $ttl = config('monitoring.cache_ttl', 30);

        return Cache::remember($cacheKey, now()->addSeconds($ttl), function () use ($filter) {
            $query = $this->build($filter);

            $counts = (clone $query)->selectRaw('
                COUNT(*) as total,
                0 as delay_count,
                0 as ng_count,
                0 as hold_count,
                0 as demurrage_count,
                0 as missing_voyage_count,
                0 as pdi_pending_count
            ')->first();

            return [
                'delay_count' => (int) ($counts->delay_count ?? 0),
                'ng_count' => (int) ($counts->ng_count ?? 0),
                'hold_count' => (int) ($counts->hold_count ?? 0),
                'demurrage_count' => (int) ($counts->demurrage_count ?? 0),
                'missing_voyage_count' => (int) ($counts->missing_voyage_count ?? 0),
                'pdi_pending_count' => (int) ($counts->pdi_pending_count ?? 0),
                'total' => (int) ($counts->total ?? 0),
            ];
        });
    }

    public function build(MonitoringFilter $filter): Builder
    {
        $query = Shipment::query();

        $query->whereNotIn('status', [\App\Enums\ShipmentStatus::Draft->value]);

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

        if (!$filter->show_finished) {
            $query->whereNotIn('status', [
                \App\Enums\ShipmentStatus::Delivered->value,
                \App\Enums\ShipmentStatus::Cancelled->value,
            ]);
        }

        // TODO Sprint 6.2: implement COUNT(*) FILTER for each exception type

        return $query;
    }
}