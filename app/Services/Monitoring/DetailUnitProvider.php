<?php

namespace App\Services\Monitoring;

use App\Queries\Monitoring\ShipmentDetailQueryBuilder;
use App\ViewModels\Monitoring\UnitDetailData;

final class DetailUnitProvider
{
    public function __construct(
        private readonly ShipmentDetailQueryBuilder $queryBuilder,
        private readonly StageResolver $stageResolver,
        private readonly ProgressCalculator $progressCalculator,
        private readonly TimelineBuilder $timelineBuilder,
        private readonly InspectionSummaryBuilder $inspectionSummaryBuilder,
        private readonly LeadTimeBuilder $leadTimeBuilder,
        private readonly AgeCalculator $ageCalculator,
    ) {}

    public function provide(int $unitId, ?int $branchId = null): UnitDetailData
    {
        // TODO Sprint 6.2: implement slide-over data orchestration
        return UnitDetailData::empty();
    }
}