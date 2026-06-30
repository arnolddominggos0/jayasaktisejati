<?php

namespace App\Services\Monitoring;

use App\DTO\Monitoring\MonitoringFilter;
use App\Queries\Monitoring\WorkspaceSummaryQueryBuilder;
use App\Support\Monitoring\PeriodResolver;
use App\ViewModels\Monitoring\WorkspaceSummaryData;
use Illuminate\Support\Carbon;

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
            period: $this->periodLabel($raw['period']),
        );
    }

    private function periodLabel(string $period): string
    {
        [$start] = PeriodResolver::bounds($period);

        return ucfirst(Carbon::instance($start)->translatedFormat('F Y'));
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