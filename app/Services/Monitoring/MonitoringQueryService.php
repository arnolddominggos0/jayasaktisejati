<?php

namespace App\Services\Monitoring;

use App\DTO\Monitoring\MonitoringFilter;
use App\Enums\ShipmentMode;
use App\Enums\ShipmentStatus;
use App\Enums\TrackStatus;
use App\Queries\Monitoring\UnitMonitoringQueryBuilder;
use Illuminate\Pagination\LengthAwarePaginator as ConcretePaginator;
use App\ViewModels\Monitoring\AgeData;
use App\ViewModels\Monitoring\CurrentStageData;
use App\ViewModels\Monitoring\MonitoringRowData;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;

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
     * Flow: QueryBuilder -> Unit models (with shipment eager-loaded) -> MonitoringRowBuilder -> MonitoringRowData[]
     */
    public function paginate(MonitoringFilter $filter): LengthAwarePaginator
    {
        $paginator = $this->queryBuilder->build($filter)
            ->paginate($filter->page_size, ['*'], 'page', $filter->page);

        $rows = $paginator->getCollection()
            ->map(fn ($unit) => $this->rowBuilder->build($unit));

        return new ConcretePaginator(
            items: $rows,
            total: $paginator->total(),
            perPage: $paginator->perPage(),
            currentPage: $paginator->currentPage(),
            options: $paginator->getOptions(),
        );
    }
}
