<?php

namespace App\Services\Monitoring;

use App\DTO\Monitoring\MonitoringFilter;
use App\Queries\Monitoring\ExceptionCountQueryBuilder;
use App\ViewModels\Monitoring\ExceptionBandData;

final class ExceptionCounterService
{
    public function __construct(
        private readonly ExceptionCountQueryBuilder $queryBuilder,
        private readonly ExceptionEvaluator $evaluator,
    ) {}

    public function count(MonitoringFilter $filter): ExceptionBandData
    {
        $raw = $this->queryBuilder->rawCounts($filter);

        return $this->evaluator->buildBand($raw);
    }
}
