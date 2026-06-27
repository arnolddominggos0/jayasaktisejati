<?php

namespace App\Services\Monitoring;

use App\Enums\ShipmentMode;
use App\Enums\TrackStatus;

final class ProgressCalculator
{
    public function calculate(TrackStatus $currentStage, bool $isHeld = false, bool $isCancelled = false): int
    {
        // TODO Sprint 6.2: implement progress calculation
        return 0;
    }
}