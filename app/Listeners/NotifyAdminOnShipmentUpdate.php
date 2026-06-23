<?php

namespace App\Listeners;

use App\Events\ShipmentStatusUpdated;
use App\Models\User;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;
use BackedEnum;

class NotifyAdminOnShipmentUpdate
{
    public function handle(ShipmentStatusUpdated $event): void
    {
        $shipment = $event->shipment;

        // super_admin is a global role with no branch restriction (branch_id = null).
        // The previous branch filter was designed for office_admin (branch-scoped).
        // Now that only super_admin exists, all super_admins receive all notifications.
        $recipients = User::role('super_admin')->get();

        if ($recipients->isEmpty()) {
            return;
        }

        $url = route('filament.admin.resources.shipments.edit', ['record' => $shipment]);

        $statusText = match (true) {
            $shipment->status instanceof BackedEnum => $shipment->status->value,
            is_string($shipment->status) => $shipment->status,
            default => '(tanpa status)',
        };

        $title = 'Status pengiriman diperbarui';
        $body  = sprintf(
            'Shipment %s sekarang %s.',
            $shipment->code ?? '#',
            $statusText
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
