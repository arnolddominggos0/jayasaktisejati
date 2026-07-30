<?php

namespace App\Services;

use App\Models\Depot;
use App\Models\Shipment;
use App\Models\User;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;


class NewOperationalTaskNotifier
{

    public static function notifyForNewShipment(Shipment $shipment): void
    {
        $depotId = $shipment->assigned_depot_id;
        if (! $depotId) {
            return;
        }

        $recipients = self::resolveDepotFieldCoordinators($shipment, (int) $depotId);

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::make()
            ->title('Pekerjaan Operasional Baru')
            ->icon('heroicon-o-clipboard-document-list')
            ->body(self::buildBody($shipment))
            ->success()
            ->actions([
                Action::make('buka_tugas_operasional')
                    ->label('Buka Tugas Operasional')
                    ->url(route('filament.fc.pages.operational-tasks'))
                    ->markAsRead(),
            ])
            ->sendToDatabase($recipients);
    }
    private static function resolveDepotFieldCoordinators(Shipment $shipment, int $depotId)
    {
        $depotCoordinatorId = Depot::whereKey($depotId)->value('coordinator_user_id');

        $directIds = array_values(array_filter([
            $depotCoordinatorId,
            $shipment->coordinator_id,
        ]));

        return User::role('field_coordinator')
            ->where(function ($q) use ($depotId, $directIds) {
                $q->where(function ($w) use ($depotId) {
                    $w->where('scope_unit_type', 'depot')
                        ->where('scope_unit_id', $depotId);
                });

                if ($directIds) {
                    $q->orWhereIn('id', $directIds);
                }
            })
            ->get();
    }

    private static function buildBody(Shipment $shipment): string
    {
        $customer = $shipment->customer?->name ?? 'Pelanggan';
        $unitCount = $shipment->units()->count();
        $sppb = $shipment->doc_number ?: '—';

        $lines = [e($customer)];

        if ($unitCount > 0) {
            $lines[] = e("{$unitCount} Unit");
        }

        $lines[] = 'SPPB';
        $lines[] = e($sppb);
        $lines[] = 'Siap diproses.';

        return implode('<br>', $lines);
    }
}
