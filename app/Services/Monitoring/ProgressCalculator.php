<?php

namespace App\Services\Monitoring;

use App\Enums\TrackStatus;

final class ProgressCalculator
{
    public function calculate(TrackStatus $currentStage, bool $isHeld = false, bool $isCancelled = false): int
    {
        if ($isCancelled) {
            return 0;
        }

        if ($currentStage === TrackStatus::Delivered) {
            return 100;
        }

        $order = TrackStatus::orderSea();
        $total = count($order) - 1;

        $index = array_search($currentStage, $order, true);

        if ($index === false || $total <= 0) {
            return 0;
        }

        return (int) round(($index / $total) * 100);
    }
}