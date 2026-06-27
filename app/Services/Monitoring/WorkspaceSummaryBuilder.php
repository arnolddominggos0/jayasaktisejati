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
            activeUnits: $raw['active_units'],
            finishedUnits: $raw['finished_units'],
            route: $this->routeLabel($raw['route']),
            branch: $raw['branch'],
            lastRefresh: $raw['refreshed_at'],
            filteredUnits: $raw['filtered_units'],
        );
    }

    private function routeLabel(string $route): string
    {
        return match ($route) {
            'tam' => 'TAM',
            'all' => 'Semua',
            default => ucfirst($route),
        };
    }
}