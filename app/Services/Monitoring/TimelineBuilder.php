<?php

namespace App\Services\Monitoring;

use App\Enums\TrackStatus;
use App\Models\Shipment;
use App\ViewModels\Monitoring\TimelineStage;
use App\ViewModels\Monitoring\UnitTimeline;

final class TimelineBuilder
{
    public function build(Shipment $shipment): UnitTimeline
    {
        $order = TrackStatus::orderForMode($shipment->mode);

        $tracks = $shipment->relationLoaded('tracks') ? $shipment->tracks : collect();

        // Index completed tracks by status value (only those with a real timestamp)
        $completedMap = $tracks
            ->filter(fn($t) => $t->tracked_at !== null)
            ->keyBy(fn($t) => $t->status instanceof TrackStatus
                ? $t->status->value
                : (string) $t->status);

        // Find the last completed stage by order index
        $lastCompletedIndex = -1;
        foreach ($order as $i => $status) {
            if ($completedMap->has($status->value)) {
                $lastCompletedIndex = $i;
            }
        }

        $stages = [];
        $completedCount = 0;

        foreach ($order as $i => $status) {
            $track = $completedMap->get($status->value);

            if ($i <= $lastCompletedIndex) {
                // Stage is in the past — completed regardless of whether it has a timestamp.
                // tracked_at may be null if the stage was skipped or not explicitly recorded.
                $state = 'completed';
                $completedCount++;
            } elseif ($i === $lastCompletedIndex + 1) {
                $state = 'current';
            } else {
                $state = 'future';
            }

            $stages[] = new TimelineStage(
                status: $status,
                label: $status->label(),
                icon: $status->icon(),
                color_zone: $status->color(),
                state: $state,
                tracked_at: $track?->tracked_at,
                note: $track?->note,
                location: $track?->location,
                plan_loading_time_at: $track?->plan_loading_time_at,
                plan_closing_time_at: $track?->plan_closing_time_at,
                actual_loading_time_at: $track?->actual_loading_time_at,
                actual_closing_time_at: $track?->actual_closing_time_at,
            );
        }

        return new UnitTimeline(
            stages: $stages,
            completed_count: $completedCount,
            total_count: count($stages),
        );
    }
}