<?php

namespace App\Filament\Resources\ShipmentHistoryResource\Pages;

use App\Filament\Resources\ShipmentHistoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditShipmentHistory extends EditRecord
{
    protected static string $resource = ShipmentHistoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function authorizeAccess(): void
    {
        parent::authorizeAccess();

        $u = \Filament\Facades\Filament::auth()->user();
        if ($this->record && $this->record->isHistorical() && ! $u?->hasRole('super_admin')) {
            \Filament\Notifications\Notification::make()
                ->title('Riwayat bersifat read-only')
                ->body('Pengiriman sudah Terkirim/Dibatalkan. Hanya super admin yang dapat mengedit riwayat.')
                ->warning()->send();

            $this->redirect(\App\Filament\Resources\ShipmentHistoryResource::getUrl('view', ['record' => $this->record]));
        }
    }
}
