<?php

namespace App\Filament\Resources\ShipmentTrackingResource\Widgets;

use App\Enums\TrackStatus;
use App\Models\ShipmentTrack;
use Filament\Widgets\Widget;
use Spatie\Activitylog\Models\Activity;

class RecentTrackingActivities extends Widget
{
    protected static string $view = 'filament.widgets.recent-tracking-activities';
    protected int|string|array $columnSpan = 'full';
    protected static ?string $pollingInterval = '30s';

    public ?int $shipmentId = null;

    protected function getViewData(): array
    {
        $events = [
            'track_created',
            'track_status_changed',
            'track_location_changed',
            'track_eta_changed',
            'track_updated',
            'track_deleted',
            'track_restored',
        ];

        $activities = Activity::query()
            ->where('log_name', 'tracking')
            ->whereIn('event', $events)
            ->when($this->shipmentId, function ($q) {
                $q->whereHasMorph(
                    'subject',
                    [ShipmentTrack::class],
                    fn ($sq) => $sq->where('shipment_id', $this->shipmentId)
                );
            })
            ->with([
                'causer',
                'subject' => fn ($morph) =>
                    $morph->morphWith([
                        ShipmentTrack::class => ['shipment'],
                    ]),
            ])
            ->latest('created_at')
            ->limit(30)
            ->get();

        return compact('activities');
    }

    public static function badgeColor(null|string|TrackStatus $status): string
    {
        $value = $status instanceof TrackStatus ? $status->value : $status;

        return match ($value) {
            TrackStatus::Delivered->value   => 'success',
            TrackStatus::Hold->value        => 'warning',
            TrackStatus::Cancelled->value   => 'danger',

            TrackStatus::Pickup->value,
            TrackStatus::Handover->value,
            TrackStatus::Stuffing->value,
            TrackStatus::DeliveryToPort->value,
            TrackStatus::Stacking->value,
            TrackStatus::UnitLoading->value,
            TrackStatus::OnShip->value,
            TrackStatus::VesselDepart->value,
            TrackStatus::VesselArrival->value,
            TrackStatus::Unloading->value,
            TrackStatus::DeliveryToCustomer->value
                => 'info',

            default => 'gray',
        };
    }
}
