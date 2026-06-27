<?php

namespace App\Services\Monitoring;

use App\DTO\Monitoring\MonitoringFilter;
use App\Queries\Monitoring\ExceptionCountQueryBuilder;
use App\ViewModels\Monitoring\ExceptionBandData;

final class ExceptionCounterService
{
    public function __construct(
        private readonly ExceptionCountQueryBuilder $queryBuilder,
    ) {}

    public function count(MonitoringFilter $filter): ExceptionBandData
    {
        $raw = $this->queryBuilder->rawCounts($filter);

        return new ExceptionBandData(
            delay_count: $raw['delay_count'],
            ng_count: $raw['ng_count'],
            hold_count: $raw['hold_count'],
            demurrage_count: $raw['demurrage_count'],
            missing_voyage_count: $raw['missing_voyage_count'],
            pdi_pending_count: $raw['pdi_pending_count'],
            total: $raw['total'],
        );
    }
}