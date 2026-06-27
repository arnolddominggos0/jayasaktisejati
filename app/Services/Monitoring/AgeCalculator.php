<?php

namespace App\Services\Monitoring;

use App\Enums\ShipmentMode;
use Illuminate\Support\Carbon;
use App\ViewModels\Monitoring\AgeData;

final class AgeCalculator
{
    public function calculate(
        ?Carbon $lastTrackedAt,
        ?Carbon $requestedAt,
        string $mode = 'sea',
    ): AgeData {
        // TODO Sprint 6.2: implement age calculation with D18 fallback
        return AgeData::empty();
    }
}