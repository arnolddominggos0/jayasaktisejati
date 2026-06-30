<?php

namespace App\Services\Monitoring;

use App\DTO\Monitoring\MonitoringFilter;
use App\Queries\Monitoring\UnitMonitoringQueryBuilder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as ConcretePaginator;

final class MonitoringQueryService
{
    public function __construct(
        private readonly UnitMonitoringQueryBuilder $queryBuilder,
        private readonly MonitoringRowBuilder $rowBuilder,
    ) {}

    /**
     * Run the monitoring query for the given filter and return a paginator
     * whose items are MonitoringRowData objects (built by MonitoringRowBuilder).
     *
     * Flow: QueryBuilder → raw Shipment models → MonitoringRowBuilder → MonitoringRowData[]
     */
    public function paginate(MonitoringFilter $filter): LengthAwarePaginator
    {
        $paginator = $this->queryBuilder->build($filter)
            ->paginate($filter->page_size, ['*'], 'page', $filter->page);

        $rows = $paginator->getCollection()
            ->map(fn ($shipment) => $this->rowBuilder->build($shipment));

        return new ConcretePaginator(
            items: $rows,
            total: $paginator->total(),
            perPage: $paginator->perPage(),
            currentPage: $paginator->currentPage(),
            options: $paginator->getOptions(),
        );
    }
}
