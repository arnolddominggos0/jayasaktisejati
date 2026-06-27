<?php

namespace App\Services\Monitoring;

use App\DTO\Monitoring\MonitoringFilter;
use App\Queries\Monitoring\UnitMonitoringQueryBuilder;
use App\ViewModels\Monitoring\MonitoringRowData;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class MonitoringQueryService
{
    public function __construct(
        private readonly UnitMonitoringQueryBuilder $queryBuilder,
        private readonly StageResolver $stageResolver,
        private readonly AgeCalculator $ageCalculator,
        private readonly ProgressCalculator $progressCalculator,
        private readonly ExceptionEvaluator $exceptionEvaluator,
    ) {}

    public function paginate(MonitoringFilter $filter): LengthAwarePaginator
    {
        $pageSize = config('monitoring.page_size', 50);
        $query = $this->queryBuilder->build($filter);

        $paginator = $query->paginate($pageSize, ['*'], 'page', $filter->page);

        $rows = $paginator->getCollection()->map(fn($shipment) => $this->transform($shipment, $filter));

        return new LengthAwarePaginator(
            items: $rows,
            total: $paginator->total(),
            perPage: $paginator->perPage(),
            currentPage: $paginator->currentPage(),
            options: $paginator->getOptions(),
        );
    }

    private function transform($shipment, MonitoringFilter $filter): MonitoringRowData
    {
        return MonitoringRowData::empty();
    }
}