<?php

namespace App\Services\Monitoring;

use App\Enums\ShipmentMode;
use App\Enums\TrackStatus;
use App\Models\Shipment;
use App\ViewModels\Monitoring\CurrentStageData;

final class StageResolver
{
    public function resolve(Shipment $shipment): CurrentStageData
    {
        // TODO Sprint 6.2: implement stage resolution logic
        return CurrentStageData::empty();
    }
}