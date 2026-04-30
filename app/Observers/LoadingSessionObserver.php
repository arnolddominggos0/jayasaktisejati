<?php

namespace App\Observers;

use App\Enums\LoadingStatus;
use App\Enums\TrackStatus;
use App\Models\LoadingSession;
use App\Services\LoadingSessionAutoCreate;
use Illuminate\Support\Facades\Log;

class LoadingSessionObserver
{
    public function updated(LoadingSession $session): void
    {
        if (! $session->isDirty('status')) {
            return;
        }

        $newStatus = $session->status instanceof LoadingStatus
            ? $session->status
            : LoadingStatus::tryFrom((string) $session->status);

        if (! $newStatus || $newStatus !== LoadingStatus::Completed) {
            return;
        }

        $shipment = $session->shipment;
        if (! $shipment) {
            return;
        }

        if (! LoadingSessionAutoCreate::isRackShipment($shipment)) {
            return;
        }

        $existingUnitLoading = $shipment->tracks()
            ->where('status', TrackStatus::UnitLoading->value)
            ->whereNotNull('tracked_at')
            ->exists();

        if ($existingUnitLoading) {
            return;
        }

        try {
            $shipment->appendTrack(
                TrackStatus::UnitLoading,
                'Otomatis: Loading checkpoint selesai ('.$session->code.')',
            );

            Log::info('LoadingSessionObserver: auto-progressed to UnitLoading', [
                'session_id' => $session->id,
                'session_code' => $session->code,
                'shipment_id' => $shipment->id,
                'shipment_code' => $shipment->code,
            ]);
        } catch (\DomainException $e) {
            Log::warning('LoadingSessionObserver: could not auto-progress to UnitLoading', [
                'session_id' => $session->id,
                'shipment_id' => $shipment->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
