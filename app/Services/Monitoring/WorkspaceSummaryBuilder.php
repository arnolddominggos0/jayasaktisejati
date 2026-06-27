<?php

namespace App\Services\Monitoring;

use App\DTO\Monitoring\MonitoringFilter;
use App\Queries\Monitoring\WorkspaceSummaryQueryBuilder;
use App\ViewModels\Monitoring\WorkspaceSummaryData;

final class WorkspaceSummaryBuilder
{
    public function __construct(
        private readonly WorkspaceSummaryQueryBuilder $queryBuilder,
    ) {}

    public function build(MonitoringFilter $filter): WorkspaceSummaryData
    {
        $raw = $this->queryBuilder->rawSummary($filter);

        return new WorkspaceSummaryData(
            total_units: $raw['total_units'],
            active_shipments: $raw['active_shipments'],
            in_transit_units: $raw['in_transit_units'],
            at_port_units: $raw['at_port_units'],
            delivered_today: $raw['delivered_today'],
        );
    }
}