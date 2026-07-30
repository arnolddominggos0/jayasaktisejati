<?php

namespace App\Services;

use App\Enums\TrackStatus;
use App\Models\ShipmentTrack;
use App\Models\UnitInspection;
use App\Models\UnitInspectionItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InspectionDraftAutoCreate
{
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

        return self::ensureForShipmentAndStage($shipment, $stage);
    }

    public static function ensureForShipmentAndStage(\App\Models\Shipment $shipment, string $stage): array
    {
        $units = $shipment->units()->get(['id']);

        if ($units->isEmpty()) {
            Log::warning('InspectionDraftAutoCreate: no units on shipment', [
                'shipment_id'   => $shipment->id,
                'shipment_code' => $shipment->code,
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
            'stage'           => $stage,
            'units_processed' => $units->count(),
            'created'         => $created,
            'skipped'         => $skipped,
        ]);

        return self::result($stage, $units->count(), $created, $skipped);
    }
    private static function createItems(UnitInspection $inspection, string $stage): void
    {
        $templates = config('unit_inspection_templates', []);
        $template  = $templates[$stage] ?? null;

        if ($template === null) {
            Log::warning("InspectionDraftAutoCreate: no template for stage '{$stage}' — skipping item generation", [
                'inspection_id' => $inspection->id,
                'stage'         => $stage,
            ]);
            return;
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

    public static function criteriaHelperText(?string $category, ?string $itemName): ?string
    {
        if (blank($category) || blank($itemName)) {
            return null;
        }

        $templates = config('unit_inspection_templates', []);

        foreach ($templates as $categories) {
            foreach ((array) $categories as $templateCategory => $items) {
                if ($templateCategory !== $category) {
                    continue;
                }

                foreach ((array) $items as $item) {
                    if (($item['name'] ?? null) !== $itemName) {
                        continue;
                    }

                    $criteria = $item['criteria'] ?? null;

                    if (blank($criteria)) {
                        return null;
                    }
                    return collect((array) $criteria)->implode(' • ');
                }
            }
        }

        return null;
    }
}
