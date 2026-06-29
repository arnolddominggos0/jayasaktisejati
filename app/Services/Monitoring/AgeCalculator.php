<?php

namespace App\Services\Monitoring;

use App\ViewModels\Monitoring\AgeData;
use Illuminate\Support\Carbon;

final class AgeCalculator
{
    /**
     * Calculate age of a shipment for display and stuck detection.
     *
     * Age = days since requested_at (D0).
     * Stuck = no track activity for > stuck_days, or no track and total age > stuck_days.
     * Fallback = requested_at used because lastTrackedAt is null → label suffix "(est)".
     *
     * ADR-009: the $mode parameter was removed — v1 is sea-only and age calculation
     * has no sea/land branching. Add it back in v2 if land-mode semantics differ.
     */
    public function calculate(
        ?Carbon $lastTrackedAt,
        ?Carbon $requestedAt,
    ): AgeData {
        $stuckDays = (int) config('monitoring.stuck_days', 3);

        if ($requestedAt === null && $lastTrackedAt === null) {
            return AgeData::empty();
        }

        $fallbackUsed = ($lastTrackedAt === null);
        $ageDays      = $requestedAt !== null
            ? (int) $requestedAt->diffInDays(now())
            : null;

        $isStuck = $lastTrackedAt !== null
            ? $lastTrackedAt->diffInDays(now()) > $stuckDays
            : ($ageDays !== null && $ageDays > $stuckDays);

        $label = $ageDays !== null
            ? ('D' . $ageDays . ($fallbackUsed ? ' (est)' : ''))
            : '—';

        return new AgeData(
            days: $ageDays,
            label: $label,
            is_stuck: $isStuck,
            fallback_used: $fallbackUsed,
        );
    }
}
