<?php

namespace App\Services;

use App\Enums\TrackStatus;
use App\Models\ShipmentTrack;
use App\Models\UnitInspection;
use App\Models\UnitInspectionItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * InspectionDraftAutoCreate
 *
 * Mirrors LoadingSessionAutoCreate — called from ShipmentTrackObserver::creating().
 *
 * When a ShipmentTrack is created at a stage that requires physical inspection,
 * this service ensures one draft UnitInspection record (with pre-built items)
 * exists per unit in the shipment.
 *
 * Draft indicator:
 *   status        = 'pending'         (status column is NOT NULL, null not allowed)
 *   submitted_at  = null              (canonical "not yet submitted" check)
 *   gate_decision = null
 *
 * Idempotent: firstOrCreate on (unit_id, stage) — safe to call multiple times.
 *
 * ⚠ Side effect on admin QA:
 *   UnitResource::table() uses withCount('inspections') which includes pending drafts.
 *   The "Status Akhir" column will show 'lulus' for draft-only units.
 *   Fix: add ->where('status', '!=', UnitInspection::STATUS_PENDING) to the
 *   UnitResource inspection counts when ready — no model change required.
 */
class InspectionDraftAutoCreate
{
    /**
     * Maps TrackStatus → inspection stage key.
     *
     * Public so ShipmentUnitsRelationManager and other callers can reuse
     * the canonical mapping instead of duplicating it.
     *
     * Returns null for statuses that carry no physical inspection
     * (onship, vessel_depart, vessel_arrival, hold, cancelled, delivered,
     * delivery_to_port, stacking, unit_loading — already covered by stuffing).
     */
    public static function resolveStage(TrackStatus $status): ?string
    {
        return match ($status) {
            TrackStatus::Pickup              => 'pickup',
            TrackStatus::Handover            => 'handover_depot',
            TrackStatus::Stuffing            => 'loading',
            TrackStatus::Unloading           => 'unloading',
            TrackStatus::HandoverTrucking    => 'selfdrive',
            TrackStatus::DeliveryToCustomer  => 'dooring',
            default                          => null,
        };
    }

    /**
     * Entry point — called from ShipmentTrackObserver::creating().
     *
     * @return array{stage: string|null, units_processed: int, created: int, skipped: int}
     */
    public static function ensureForTrack(ShipmentTrack $track): array
    {
        $status = $track->status instanceof TrackStatus
            ? $track->status
            : TrackStatus::tryFrom((string) $track->status);

        if (! $status) {
            return self::result(null, 0, 0, 0);
        }

        $stage = self::resolveStage($status);

        if (! $stage) {
            return self::result(null, 0, 0, 0);
        }

        $shipment = $track->shipment;

        if (! $shipment) {
            return self::result($stage, 0, 0, 0);
        }

        $units = $shipment->units()->get(['id']);

        if ($units->isEmpty()) {
            Log::warning('InspectionDraftAutoCreate: no units on shipment', [
                'shipment_id'   => $shipment->id,
                'shipment_code' => $shipment->code,
                'track_status'  => $status->value,
                'stage'         => $stage,
            ]);

            return self::result($stage, 0, 0, 0);
        }

        $created = 0;
        $skipped = 0;

        foreach ($units as $unit) {
            DB::transaction(function () use ($unit, $stage, &$created, &$skipped) {
                $inspection = UnitInspection::firstOrCreate(
                    [
                        'unit_id' => $unit->id,
                        'stage'   => $stage,
                    ],
                    [
                        'source'        => UnitInspection::SOURCE_LIVE,
                        'status'        => UnitInspection::STATUS_PENDING,
                        'checked_by'    => null,
                        'checked_at'    => null,
                        'submitted_at'  => null,
                        'gate_decision' => null,
                        'notes'         => null,
                    ]
                );

                if ($inspection->wasRecentlyCreated) {
                    self::createItems($inspection, $stage);
                    $created++;
                } else {
                    $skipped++;
                }
            });
        }

        Log::info('InspectionDraftAutoCreate: drafts ensured', [
            'shipment_id'     => $shipment->id,
            'shipment_code'   => $shipment->code,
            'track_status'    => $status->value,
            'stage'           => $stage,
            'units_processed' => $units->count(),
            'created'         => $created,
            'skipped'         => $skipped,
        ]);

        return self::result($stage, $units->count(), $created, $skipped);
    }

    /**
     * Builds UnitInspectionItem rows for a new draft inspection.
     *
     * Template lookup mirrors UnitInspectionGenerator::buildItems():
     *   - handover_depot → inherits pickup template (config value is null)
     *   - all items default to result='ok' (inspector marks NG during submission)
     */
    private static function createItems(UnitInspection $inspection, string $stage): void
    {
        $templates = config('unit_inspection_templates', []);

        $templateKey = ($stage === 'handover_depot') ? 'pickup' : $stage;
        $template    = $templates[$templateKey] ?? null;

        // handover_depot config value is null — explicit null → fall back to pickup
        if ($template === null) {
            $template = $templates['pickup'] ?? [];
        }

        foreach ((array) $template as $category => $items) {
            foreach ($items as $item) {
                UnitInspectionItem::create([
                    'unit_inspection_id' => $inspection->id,
                    'category'           => $category,
                    'item_name'          => $item['name'],
                    'result'             => UnitInspectionItem::RESULT_OK,
                    'finding_type'       => null,
                    'notes'              => null,
                    'photo_url'          => null,
                ]);
            }
        }
    }

    private static function result(?string $stage, int $units, int $created, int $skipped): array
    {
        return [
            'stage'           => $stage,
            'units_processed' => $units,
            'created'         => $created,
            'skipped'         => $skipped,
        ];
    }
}
