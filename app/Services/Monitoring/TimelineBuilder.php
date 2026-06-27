<?php

namespace App\Services\Monitoring;

use App\Enums\ShipmentMode;
use App\Models\Shipment;
use App\ViewModels\Monitoring\UnitTimeline;

final class TimelineBuilder
{
    public function build(Shipment $shipment): UnitTimeline
    {
        // TODO Sprint 6.2: implement ordered timeline construction
        return UnitTimeline::empty();
    }
}