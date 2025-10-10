<?php

namespace App\Listeners;

use App\Events\ShipmentStatusUpdated;
use App\Models\User;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;

class NotifyAdminOnShipmentUpdate
{
    public function handle(ShipmentStatusUpdated $event): void
    {
        $shipment = $event->shipment;

        $recipients = User::role(['super_admin', 'office_admin'])
            ->when($shipment->branch_id, fn($q) => $q->where('branch_id', $shipment->branch_id))
            ->get();

        if ($recipients->isEmpty()) {
            return;
        }

        $url = route('filament.admin.resources.shipments.edit', ['record' => $shipment]);

        $title = 'Status pengiriman diperbarui';
        $body  = sprintf(
            'Shipment %s sekarang %s.',
            $shipment->code ?? '#',
            $shipment->status ?? '(tanpa status)'
        );

        Notification::make()
            ->title($title)
            ->body($body)
            ->success()
            ->actions([
                Action::make('Lihat')
                    ->url($url)
                    ->markAsRead(),
            ])
            ->sendToDatabase($recipients);
    }
}
