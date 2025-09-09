<?php

namespace App\Filament\Resources\ShipmentResource\Pages;

use App\Enums\ShipmentStatus;
use App\Filament\Resources\ShipmentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditShipment extends EditRecord
{
    protected static string $resource = ShipmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('cancel')
                ->label('Batalkan')
                ->icon('heroicon-m-x-circle')
                ->color('danger')
                ->visible(fn() => is_null($this->record->cancelled_at))
                ->requiresConfirmation()
                ->action(fn() => $this->record->update([
                    'status'       => ShipmentStatus::Cancelled->value,
                    'cancelled_at' => now(),
                    'cancelled_by' => auth()->id(),
                ])),

            Actions\Action::make('uncancel')
                ->label('Pulihkan')
                ->icon('heroicon-m-arrow-path')
                ->color('gray')
                ->visible(fn() => !is_null($this->record->cancelled_at))
                ->action(fn() => $this->record->update([
                    'cancelled_at' => null,
                    'cancelled_by' => null,
                ])),
            ...parent::getHeaderActions(),
        ];
    }
}
