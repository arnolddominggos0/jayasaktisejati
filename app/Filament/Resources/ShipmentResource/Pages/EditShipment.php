<?php

namespace App\Filament\Resources\ShipmentResource\Pages;

use App\Enums\ShipmentStatus;
use App\Events\ShipmentStatusUpdated;
use App\Filament\Resources\ShipmentResource;
use App\Filament\Resources\ShipmentResource\Widgets\RecentShipmentActivities;
use App\Filament\Resources\ShipmentTrackingResource\Widgets\RecentTrackingActivities;
use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use App\Models\Shipment;

class EditShipment extends EditRecord
{
    protected static string $resource = ShipmentResource::class;

    protected function getRedirectUrl(): string
    {
        return ShipmentResource::getUrl('index');
    }

    protected function getHeaderWidgets(): array
    {
        return [
            RecentShipmentActivities::class,
        ];
    }


    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('cancel')
                ->label('Batalkan')
                ->icon('heroicon-m-x-circle')
                ->color('danger')
                ->visible(fn() => ($this->record->status instanceof ShipmentStatus)
                    ? $this->record->status !== ShipmentStatus::Cancelled->value
                    : (string) $this->record->status !== ShipmentStatus::Cancelled->value)
                ->requiresConfirmation()
                ->action('cancelShipment'),

            Actions\Action::make('uncancel')
                ->label('Pulihkan')
                ->icon('heroicon-m-arrow-path')
                ->color('gray')
                ->visible(fn() => ($this->record->status instanceof ShipmentStatus)
                    ? $this->record->status === ShipmentStatus::Cancelled
                    : (string) $this->record->status === ShipmentStatus::Cancelled->value)
                ->requiresConfirmation()
                ->action('uncancelShipment'),

            ...parent::getHeaderActions(),
        ];
    }

    public function cancelShipment(): void
    {
        try {
            $this->record->cancel(Filament::auth()->id());

            Notification::make()
                ->title('Permintaan dibatalkan')
                ->body("{$this->record->code} berhasil dibatalkan.")
                ->success()
                ->send();

            $this->redirect(ShipmentResource::getUrl('index'));
        } catch (\DomainException $e) {
            Notification::make()
                ->title('Tidak bisa dibatalkan')
                ->body($e->getMessage())
                ->danger()
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Gagal membatalkan')
                ->body('Terjadi kesalahan saat membatalkan pesanan.')
                ->danger()
                ->send();
        }
    }

    public function uncancelShipment(): void
    {
        try {
            $this->record->uncancel(Filament::auth()->id());

            Notification::make()
                ->title('Permintaan dipulihkan')
                ->body("{$this->record->code} berhasil dipulihkan.")
                ->success()
                ->send();

            $this->redirect(ShipmentResource::getUrl('index'));
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Gagal memulihkan')
                ->body('Terjadi kesalahan saat memulihkan pesanan.')
                ->danger()
                ->send();
        }
    }

    public function afterSave(): void
    {
        $shipment = $this->record;

        $this->syncUnits($shipment, $this->form->getState());

        event(new ShipmentStatusUpdated($shipment, 'fc'));
    }

    protected function syncUnits(Shipment $shipment, array $state): void
    {
        $units = $state['units'] ?? [];

        $shipment->units()->delete();

        if (is_array($units) && count($units) > 0) {
            foreach ($units as $u) {
                $shipment->units()->create([
                    'model_no'          => $u['model_no'] ?? null,
                    'reg_no'            => $u['reg_no'] ?? null,
                    'chassis_no'        => $u['chassis_no'] ?? null,
                    'engine_no'         => $u['engine_no'] ?? null,
                    'color'             => $u['color'] ?? null,
                    'do_number'         => $u['do_number'] ?? null,
                    'qty'               => isset($u['qty']) ? (int)$u['qty'] : 1,
                    'container_display' => $u['container_display'] ?? null,
                    'notes'             => $u['notes'] ?? null,
                ]);
            }
        }
    }
}
