<?php

namespace App\Services;

use App\Enums\LoadingOperationType;
use App\Enums\LoadingStatus;
use App\Enums\TrackStatus;
use App\Models\LoadingSession;
use App\Models\Shipment;
use App\Models\ShipmentTrack;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class LoadingSessionAutoCreate
{
    public static function syncingStatuses(): array
    {
        return [
            TrackStatus::Stacking->value,
            TrackStatus::DeliveryToPort->value,
        ];
    }

    public static function isRackShipment(Shipment $shipment): bool
    {
        $vehicleLoading = $shipment->vehicle_loading;

        if ($shipment->mode?->value === 'sea' || $shipment->mode?->value === 'sea_freight') {
            return in_array($vehicleLoading, ['rack', 'flat_rack'], true);
        }

        return false;
    }

    public static function ensureForTrack(ShipmentTrack $track): ?LoadingSession
    {
        $shipment = $track->shipment;
        if (! $shipment) {
            return null;
        }

        if (! self::isRackShipment($shipment)) {
            return null;
        }

        $status = $track->status instanceof TrackStatus
            ? $track->status
            : TrackStatus::tryFrom((string) $track->status);

        if (! in_array($status?->value, self::syncingStatuses(), true)) {
            return null;
        }

        return self::forShipment($shipment);
    }

    public static function forShipment(Shipment $shipment): ?LoadingSession
    {
        if (! self::isRackShipment($shipment)) {
            return null;
        }

        $existing = LoadingSession::where('shipment_id', $shipment->id)
            ->where('operation_type', LoadingOperationType::Loading->value)
            ->first();

        if ($existing) {
            return $existing;
        }

        if (! $shipment->assigned_depot_id) {
            Log::warning('LoadingSessionAutoCreate: shipment has no assigned_depot_id', [
                'shipment_id' => $shipment->id,
                'code' => $shipment->code,
            ]);

            return null;
        }

        $coordinatorId = self::resolveCoordinator($shipment);

        $session = LoadingSession::create([
            'code' => LoadingSession::generateCode(),
            'shipment_id' => $shipment->id,
            'depot_id' => $shipment->assigned_depot_id,
            'branch_id' => $shipment->branch_id,
            'coordinator_user_id' => $coordinatorId,
            'operation_type' => LoadingOperationType::Loading->value,
            'status' => LoadingStatus::Draft->value,
            'current_step' => 'mp_attendance_check',
            'mp_required' => 0,
            'mp_present' => 0,
            'started_at' => now(),
        ]);

        Log::info('LoadingSessionAutoCreate: created loading session for rack shipment', [
            'session_id' => $session->id,
            'code' => $session->code,
            'shipment_id' => $shipment->id,
            'shipment_code' => $shipment->code,
            'vehicle_loading' => $shipment->vehicle_loading,
        ]);

        return $session;
    }

    public static function canTransitionTo(Shipment $shipment, TrackStatus $targetStatus): bool
    {
        if (! in_array($targetStatus->value, [TrackStatus::UnitLoading->value], true)) {
            return true;
        }

        if (! self::isRackShipment($shipment)) {
            return true;
        }

        $session = LoadingSession::where('shipment_id', $shipment->id)
            ->where('operation_type', LoadingOperationType::Loading->value)
            ->first();

        if (! $session) {
            return false;
        }

        return $session->status === LoadingStatus::Completed;
    }

    protected static function resolveCoordinator(Shipment $shipment): ?int
    {
        if ($shipment->assigned_depot_id) {
            $depot = $shipment->depot;
            if ($depot && $depot->coordinator_user_id) {
                return $depot->coordinator_user_id;
            }
        }

        return $shipment->branch_id
            ? User::where('branch_id', $shipment->branch_id)
                ->whereHas('roles', fn ($q) => $q->where('name', 'field_coordinator'))
                ->value('id')
            : null;
    }
}
