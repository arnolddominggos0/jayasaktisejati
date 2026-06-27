<?php

namespace App\Services\Monitoring;

use App\Models\Unit;
use App\ViewModels\Monitoring\InspectionSummary;

final class InspectionSummaryBuilder
{
    public function build(Unit $unit): InspectionSummary
    {
        // TODO Sprint 6.2: implement per-unit inspection summary
        return InspectionSummary::empty();
    }
}