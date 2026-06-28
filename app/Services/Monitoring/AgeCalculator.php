<?php

namespace App\Services\Monitoring;

use Illuminate\Support\Carbon;
use App\ViewModels\Monitoring\AgeData;

final class AgeCalculator
{
    public function calculate(
        ?Carbon $lastTrackedAt,
        ?Carbon $requestedAt,
        string $mode = 'sea',
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