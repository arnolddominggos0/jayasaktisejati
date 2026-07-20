<?php

namespace App\Services\Monitoring;

use App\Enums\ShipmentMode;
use App\Enums\ShipmentStatus;
use App\Enums\TrackStatus;
use App\Models\Shipment;
use App\ViewModels\Monitoring\ExceptionBandData;
use App\ViewModels\Monitoring\ExceptionChipData;

final class ExceptionEvaluator
{
    /**
     * Priority order: Hold → NG → Demurrage → Delay → Stuck → Missing Voyage.
     *
     * severity values:
     *   'critical' → red  in the per-row table (severity === 'critical' check in blade)
     *   'warning'  → amber in the per-row table
     */
    private const CHIP_DEFINITIONS = [
        'hold' => [
            'label'    => 'Hold',
            'severity' => 'critical',
            'color'    => 'red',
            'icon'     => 'heroicon-o-pause-circle',
        ],
        'ng' => [
            'label'    => 'NG',
            'severity' => 'critical',
            'color'    => 'red',
            'icon'     => 'heroicon-o-x-circle',
        ],
        'demurrage' => [
            'label'    => 'Demurrage',
            'severity' => 'warning',
            'color'    => 'amber',
            'icon'     => 'heroicon-o-exclamation-triangle',
        ],
        'delay' => [
            'label'    => 'Delay',
            'severity' => 'critical',
            'color'    => 'red',
            'icon'     => 'heroicon-o-clock',
        ],
        'stuck' => [
            'label'    => 'Stuck',
            'severity' => 'warning',
            'color'    => 'amber',
            'icon'     => 'heroicon-o-arrow-path',
        ],
        'missing_voyage' => [
            'label'    => 'Missing Voyage',
            'severity' => 'warning',
            'color'    => 'amber',
            'icon'     => 'heroicon-o-map',
        ],
    ];

    // ── Band aggregation ──────────────────────────────────────────────────────

    /**
     * Build ExceptionBandData from raw aggregate counts.
     * Chips with count = 0 are excluded; priority order enforced via CHIP_DEFINITIONS.
     *
     * @param array{delay:int, hold:int, ng:int, demurrage:int, missing_voyage:int, stuck:int} $rawCounts
     */
    public function buildBand(array $rawCounts): ExceptionBandData
    {
        $chips = [];

        foreach (self::CHIP_DEFINITIONS as $type => $def) {
            $count = (int) ($rawCounts[$type] ?? 0);

            if ($count === 0) {
                continue;
            }

            $chips[] = new ExceptionChipData(
                type: $type,
                label: $def['label'],
                severity: $def['severity'],
                color: $def['color'],
                count: $count,
                icon: $def['icon'],
            );
        }

        return new ExceptionBandData(
            hold_count: (int) ($rawCounts['hold'] ?? 0),
            ng_count: (int) ($rawCounts['ng'] ?? 0),
            demurrage_count: (int) ($rawCounts['demurrage'] ?? 0),
            delay_count: (int) ($rawCounts['delay'] ?? 0),
            stuck_count: (int) ($rawCounts['stuck'] ?? 0),
            missing_voyage_count: (int) ($rawCounts['missing_voyage'] ?? 0),
            total: array_sum(array_map(
                fn (string $k) => (int) ($rawCounts[$k] ?? 0),
                array_keys(self::CHIP_DEFINITIONS)
            )),
            chips: $chips,
        );
    }

    // ── Per-row evaluation ────────────────────────────────────────────────────

    /**
     * Evaluate exception chips for a single shipment row.
     * Requires eager-loaded latestTrack; uses has_ng_inspection computed column.
     * No DB queries — all data must already be on the model.
     *
     * @return array<ExceptionChipData>
     */
    public function evaluate(Shipment $shipment): array
    {
        $demurrageDays = (int) config('monitoring.demurrage_days', 7);
        $stuckDays     = (int) config('monitoring.stuck_days', 3);

        $statusValue = $shipment->status instanceof ShipmentStatus
            ? $shipment->status->value
            : (string) $shipment->status;

        $isFinished = in_array($statusValue, [
            ShipmentStatus::Delivered->value,
            ShipmentStatus::Cancelled->value,
        ], true);

        $chips = [];

        // 1. Hold
        if ($statusValue === ShipmentStatus::Hold->value) {
            $chips[] = $this->makeChip('hold');
        }

        // 2. NG — computed column from UnitMonitoringQueryBuilder
        if ((bool) ($shipment->has_ng_inspection ?? false)) {
            $chips[] = $this->makeChip('ng');
        }

        if (! $isFinished) {
            $latestTrack    = $shipment->latestTrack;
            $latestTrackedAt = $latestTrack?->tracked_at;
            $latestStatus   = $latestTrack?->status instanceof TrackStatus
                ? $latestTrack->status->value
                : ($latestTrack?->status ? (string) $latestTrack->status : null);

            // 3. Demurrage: latest status at port-related stage for > N days
            $portStatuses = ['stacking', 'delivery_to_port', 'vessel_arrival', 'unloading'];
            if ($latestStatus !== null
                && in_array($latestStatus, $portStatuses, true)
                && $latestTrackedAt !== null
                && $latestTrackedAt->diffInDays(now()) > $demurrageDays
            ) {
                $chips[] = $this->makeChip('demurrage');
            }

            // 4. Delay: past ETA, not held
            if ($statusValue !== ShipmentStatus::Hold->value
                && $shipment->eta instanceof \Illuminate\Support\Carbon
                && $shipment->eta->isPast()
            ) {
                $chips[] = $this->makeChip('delay');
            }

            // 5. Stuck: no activity for > stuck_days
            $activityBase = $latestTrackedAt
                ?? $shipment->requested_at
                ?? $shipment->created_at;
            if ($activityBase !== null && $activityBase->diffInDays(now()) > $stuckDays) {
                $chips[] = $this->makeChip('stuck');
            }

            // 6. Missing Voyage: sea shipment without a voyage assigned
            $modeValue = $shipment->mode instanceof ShipmentMode
                ? $shipment->mode->value
                : (string) $shipment->mode;
            if ($modeValue === 'sea' && empty($shipment->voyage_id)) {
                $chips[] = $this->makeChip('missing_voyage');
            }
        }

        return $chips;
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function makeChip(string $type): ExceptionChipData
    {
        $def = self::CHIP_DEFINITIONS[$type];

        return new ExceptionChipData(
            type: $type,
            label: $def['label'],
            severity: $def['severity'],
            color: $def['color'],
            count: 1,
            icon: $def['icon'],
        );
    }

    public static function types(): array
    {
        return array_keys(self::CHIP_DEFINITIONS);
    }
}
