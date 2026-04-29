<?php

namespace App\Filament\Resources\DepotResource\Pages;

use App\Filament\Resources\DepotResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions;
use Filament\Notifications\Notification;

class EditDepot extends EditRecord
{
    protected static string $resource = DepotResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->before(function ($record, $action) {
                    if ($record->shipments()->exists()) {
                        $action->halt();

                        Notification::make()
                            ->title('Tidak bisa menghapus depo')
                            ->body('Depo masih dipakai oleh shipment. Pindahkan dulu shipment ke depo lain.')
                            ->danger()
                            ->send();
                    }
                })
                ->using(function ($record) {
                    $record->delete();
                })
                ->successNotificationTitle('Depo dihapus'),
        ];
    }
}
