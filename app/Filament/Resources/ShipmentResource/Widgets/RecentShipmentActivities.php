<?php

namespace App\Filament\Resources\ShipmentResource\Widgets;

use Filament\Widgets\Widget;
use Spatie\Activitylog\Models\Activity;
use App\Models\Shipment;

class RecentShipmentActivities extends Widget
{
    protected static string $view = 'filament.widgets.recent-shipment-activities';
    protected int|string|array $columnSpan = 3;
    protected static ?string $pollingInterval = '30s';

    protected function getViewData(): array
    {
        $activities = Activity::query()
            ->where('log_name', 'permintaan_pengiriman')
            ->where('subject_type', Shipment::class)
            ->whereIn('event', ['created', 'status_changed'])
            ->with(['causer', 'subject'])
            ->latest('created_at')
            ->limit(30)
            ->get();

        return compact('activities');
    }

    public static function badgeColor(?string $status): string
    {
        return match ($status) {
            'draft' => 'gray',
            'pending','hold' => 'warning',
            'pickup','transit' => 'info',
            'delivered' => 'success',
            'cancelled' => 'danger',
            default => 'gray',
        };
    }
}
