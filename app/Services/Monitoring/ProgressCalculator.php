<?php

namespace App\Services\Monitoring;

use App\Enums\TrackStatus;

final class ProgressCalculator
{
    /**
     * Calculate shipment progress as a percentage (0–100).
     *
     * Uses TrackStatus::toNormalizedValue() which maps stages linearly 10→120
     * (Pickup=10, Delivered=120). Dividing by 120 gives a 0–100% range.
     * Hold / Cancelled (≥900) cannot report meaningful progress without knowing
     * the pre-hold stage, so they return 0.
     */
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
