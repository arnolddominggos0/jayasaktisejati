<?php

namespace App\Services\Monitoring;

use App\Enums\TrackStatus;
use App\Models\Shipment;
use App\ViewModels\Monitoring\LeadTimeData;
use App\ViewModels\Monitoring\LeadTimeStageData;
use Illuminate\Support\Carbon;

final class LeadTimeBuilder
{
    public function build(Shipment $shipment): ?LeadTimeData
    {
        $tracks = ($shipment->relationLoaded('tracks') ? $shipment->tracks : collect())
            ->filter(fn($t) => $t->tracked_at !== null)
            ->sortBy('tracked_at')
            ->values();

        $toVal = fn($s) => $s instanceof TrackStatus ? $s->value : (string) $s;

        $first = fn(array $set) => optional(
            $tracks->first(fn($t) => in_array($toVal($t->status), $set, true))
        )->tracked_at;

        $last = fn(string $s) => optional(
            $tracks->last(fn($t) => $toVal($t->status) === $s)
        )->tracked_at;

        $pickupAt = $first([
            TrackStatus::Pickup->value,
            TrackStatus::Handover->value,
            TrackStatus::Stuffing->value,
            TrackStatus::DeliveryToPort->value,
            TrackStatus::Stacking->value,
        ]);

        $onboardAt = $first([
            TrackStatus::UnitLoading->value,
            TrackStatus::OnShip->value,
            TrackStatus::VesselDepart->value,
        ]);

        $arrivedAt = $first([
            TrackStatus::VesselArrival->value,
            TrackStatus::Unloading->value,
        ]);

        $delivAt = $last(TrackStatus::Delivered->value);

        $thresholds = config('jss_kpi.manado.thresholds', [
            'dwelling_days' => 6,
            'sailing_days'  => 10,
            'dooring_days'  => 3,
            'total_days'    => ['normal' => 19, 'urgent' => 17],
        ]);

        $priority = ($shipment->priority === 'urgent') ? 'urgent' : 'normal';
        $totalLimit = is_array($thresholds['total_days'] ?? null)
            ? ($thresholds['total_days'][$priority] ?? 19)
            : (int) ($thresholds['total_days'] ?? 19);

        $diff = fn($from, $to): ?int => ($from && $to)
            ? (int) Carbon::parse($from)->startOfDay()->diffInDays(Carbon::parse($to)->startOfDay())
            : null;

        $statusOf = fn(?int $actual, int $limit): string => match (true) {
            $actual === null => 'pending',
            $actual <= $limit => 'ok',
            default => 'late',
        };

        $dw = $diff($pickupAt, $onboardAt);
        $sa = $diff($onboardAt, $arrivedAt);
        $dr = $diff($arrivedAt, $delivAt);
        $tt = ($dw !== null && $sa !== null && $dr !== null) ? ($dw + $sa + $dr) : null;

        $dwLimit = (int) ($thresholds['dwelling_days'] ?? 6);
        $saLimit = (int) ($thresholds['sailing_days'] ?? 10);
        $drLimit = (int) ($thresholds['dooring_days'] ?? 3);

        $stages = [
            new LeadTimeStageData('dwelling', $dw, $dwLimit, $statusOf($dw, $dwLimit)),
            new LeadTimeStageData('sailing', $sa, $saLimit, $statusOf($sa, $saLimit)),
            new LeadTimeStageData('dooring', $dr, $drLimit, $statusOf($dr, $drLimit)),
            new LeadTimeStageData('total', $tt, $totalLimit, $statusOf($tt, $totalLimit)),
        ];

        $totalStatus = $statusOf($tt, $totalLimit);
        $totalBadge = match ($totalStatus) {
            'ok'   => 'On Time',
            'late' => 'Late',
            default => 'Pending',
        };

        $summaryText = $tt !== null
            ? sprintf('Total %d/%d | Dw %s/%d | Sai %s/%d | Dor %s/%d',
                $tt, $totalLimit,
                $dw ?? '-', $dwLimit,
                $sa ?? '-', $saLimit,
                $dr ?? '-', $drLimit)
            : null;

        return new LeadTimeData(
            stages: $stages,
            total_badge: $totalBadge,
            summary_text: $summaryText,
        );
    }
}