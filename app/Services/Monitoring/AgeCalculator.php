<?php

namespace App\Services\Monitoring;

use Illuminate\Support\Carbon;
use App\ViewModels\Monitoring\AgeData;

final class AgeCalculator
{
    /**
     * Calculate age of a shipment for display and stuck detection.
     *
     * Age = days since requested_at (D0).
     * Stuck = no track activity for > stuck_days, or no track and total age > stuck_days.
     * Fallback = requested_at used because lastTrackedAt is null → label suffix "(est)".
     *
     * The $mode parameter was removed — sea-only, and age calculation has no
     * sea/land branching. Add it back if land-mode semantics ever differ.
     */
    public function calculate(
        ?Carbon $lastTrackedAt,
        ?Carbon $requestedAt,
    ): AgeData {
        $stuckDays = config('monitoring.stuck_days', 3);
        $fallbackUsed = false;

        $from = $lastTrackedAt;

        if ($from === null && $requestedAt !== null) {
            $from = $requestedAt;
            $fallbackUsed = true;
        }

        if ($from === null) {
            return AgeData::empty();
        }

        $days = (int) Carbon::parse($from)->startOfDay()->diffInDays(now()->startOfDay());
        $isStuck = $days >= $stuckDays;

        $label = match (true) {
            $days === 0 => 'Hari ini',
            $days === 1 => 'Kemarin',
            default => "D+{$days}",
        };

        return new AgeData(
            days: $days,
            label: $label,
            is_stuck: $isStuck,
            fallback_used: $fallbackUsed,
        );
    }
}
