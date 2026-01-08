<?php

namespace App\Filament\Resources\VesselPlanResource\Pages;

use App\Filament\Resources\VesselPlanResource;
use App\Filament\Resources\VesselPlanResource\Widgets\VesselPlanAnalysis;
use App\Filament\Resources\VesselPlanResource\Widgets\VesselPlanDashboard;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditVesselPlan extends EditRecord
{
    protected static string $resource = VesselPlanResource::class;

    protected function getHeaderWidgets(): array
    {
        return $this->record->isFinal()
            ? [VesselPlanDashboard::class]
            : [VesselPlanAnalysis::class];
    }

    public function getHeaderWidgetsColumns(): int
    {
        return 1;
    }

    protected function getHeaderActions(): array
    {
        return [

            Action::make('kirim_ke_tam')
                ->label('Kirim ke TAM (WhatsApp)')
                ->icon('heroicon-o-paper-airplane')
                ->color('primary')
                ->visible(fn () => $this->record->isDraft())
                ->disabled(fn () => ! $this->record->canSendToTam())
                ->tooltip(fn () => $this->sendDisabledReason())
                ->requiresConfirmation()
                ->action(function () {

                    $this->record->markAsSent(auth()->id());

                    Notification::make()
                        ->title('Draft Dikirim ke TAM')
                        ->body('WhatsApp akan dibuka untuk mengirim draft jadwal.')
                        ->success()
                        ->send();

                    $this->redirect($this->record->waUrl());
                }),

            Action::make('finalize')
                ->label('Finalisasi & Import Voyage')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->visible(fn () => $this->record->isSent())
                ->requiresConfirmation()
                ->action(function () {

                    $count = $this->record->generateVoyages(auth()->id());

                    Notification::make()
                        ->title('Vessel Plan Difinalisasi')
                        ->body("{$count} voyage berhasil dibuat.")
                        ->success()
                        ->send();
                }),

            Action::make('hapus')
                ->label('Hapus Vessel Plan')
                ->color('danger')
                ->visible(fn () => $this->record->isDraft())
                ->requiresConfirmation()
                ->action(fn () => $this->record->delete()),
        ];
    }

    protected function sendDisabledReason(): string
    {
        if ($this->record->items()->count() === 0) {
            return 'Tambahkan jadwal kapal terlebih dahulu.';
        }

        $analysis = $this->record->analyze();

        if (! ($analysis['ok'] ?? false)) {
            return 'Jadwal melanggar SOP (jarak ETD terlalu jauh).';
        }

        return '';
    }
}
