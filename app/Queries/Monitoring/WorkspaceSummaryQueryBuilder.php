<?php

namespace App\Queries\Monitoring;

use App\DTO\Monitoring\MonitoringFilter;
use Illuminate\Support\Facades\Cache;

final class WorkspaceSummaryQueryBuilder
{
    public function rawSummary(MonitoringFilter $filter): array
    {
        $cacheKey = 'monitoring:summary:' . $filter->cacheKey();
        $ttl = config('monitoring.cache_ttl', 30);

        return Cache::remember($cacheKey, now()->addSeconds($ttl), function () use ($filter) {
            // TODO Sprint 6.2: implement aggregate query
            return [
                'total_units' => 0,
                'active_shipments' => 0,
                'in_transit_units' => 0,
                'at_port_units' => 0,
                'delivered_today' => 0,
            ];
        });
    }
}