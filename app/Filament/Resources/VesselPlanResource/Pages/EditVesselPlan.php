<?php

namespace App\Filament\Resources\VesselPlanResource\Pages;

use App\Enums\VesselPlanStatus;
use App\Filament\Resources\VesselPlanResource;
use App\Filament\Resources\VesselPlanResource\Widgets\VesselPlanAnalysis;
use App\Filament\Resources\VesselPlanResource\Widgets\VesselPlanDashboard;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditVesselPlan extends EditRecord
{
    protected static string $resource = VesselPlanResource::class;

    protected function getHeaderWidgets(): array
    {
        if ($this->record->isFinal()) {
            return [VesselPlanDashboard::class];
        }

        return [VesselPlanAnalysis::class];
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
                ->visible(
                    fn() =>
                    $this->record->isEditable()
                )
                ->disabled(
                    fn() =>
                    ! $this->record->canSendToTam()
                )
                ->requiresConfirmation()
                ->action(function () {

                    $this->record->markAsSent(auth()->id());

                    Notification::make()
                        ->title('Draft Dikirim ke TAM')
                        ->success()
                        ->send();

                    $this->redirect($this->record->waUrl());
                }),

            Action::make('approve')
                ->label('Setujui & Finalisasi')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(
                    fn() =>
                    $this->record->isSent()
                )
                ->requiresConfirmation()
                ->action(function () {

                    $count = $this->record->approve(auth()->id());

                    Notification::make()
                        ->title('Vessel Plan Disetujui')
                        ->body("{$count} voyage berhasil dibuat.")
                        ->success()
                        ->send();
                }),

            Action::make('reject')
                ->label('Tolak / Kembalikan')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(
                    fn() =>
                    $this->record->isSent()
                )
                ->form([
                    Textarea::make('reason')
                        ->label('Alasan Penolakan')
                        ->required()
                        ->rows(4),
                ])
                ->requiresConfirmation()
                ->action(function ($record, array $data) {

                    $record->reject(
                        $data['reason'],
                        auth()->id()
                    );

                    Notification::make()
                        ->title('Vessel Plan Ditolak')
                        ->warning()
                        ->send();
                }),

            Action::make('hapus')
                ->label('Hapus Vessel Plan')
                ->color('danger')
                ->visible(
                    fn() =>
                    $this->record->isDraft()
                )
                ->requiresConfirmation()
                ->action(fn() => $this->record->delete()),
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

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if ($this->record->isRevision()) {
            $data['status'] = VesselPlanStatus::Draft;
            $data['feedback_reason'] = null;
            $data['feedback_by'] = null;
            $data['feedback_at'] = null;
        }

        return $data;
    }
}
