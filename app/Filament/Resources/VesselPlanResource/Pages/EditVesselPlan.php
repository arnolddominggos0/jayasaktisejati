<?php

namespace App\Filament\Resources\VesselPlanResource\Pages;

use App\Filament\Resources\VesselPlanResource;
use App\Filament\Resources\VesselPlanResource\Widgets\VesselPlanAnalysis;
use App\Filament\Resources\VesselPlanResource\Widgets\VesselPlanDashboard;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditVesselPlan extends EditRecord
{
    protected static string $resource = VesselPlanResource::class;

    protected function getHeaderWidgets(): array
    {
        if ($this->record->isFinal()) {
            return [
                VesselPlanDashboard::class,
            ];
        }

        return [
            VesselPlanAnalysis::class,
        ];
    }


    public function getHeaderWidgetsColumns(): int
    {
        return 1;
    }

    protected function getHeaderActions(): array
    {
        return [

            Action::make('send_to_tam')
                ->label('Kirim ke TAM (WA)')
                ->icon('heroicon-o-paper-airplane')
                ->color('primary')
                ->visible(fn() => $this->record->isDraft())
                ->url(fn() => $this->record->waUrl())
                ->openUrlInNewTab()
                ->action(fn() => $this->record->markAsSent(auth_user()->id())),

            Action::make('finalize')
                ->label('Finalisasi & Import Voyage')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn() => $this->record->isSent())
                ->action(function () {
                    $this->record->finalizeAndImport(auth_user()->id());

                    Notification::make()
                        ->title('Vessel Plan Final')
                        ->body('Voyage berhasil dibuat.')
                        ->success()
                        ->send();
                }),

            Action::make('view_voyages')
                ->label('Lihat Voyage')
                ->icon('heroicon-o-eye')
                ->color('gray')
                ->visible(fn() => $this->record->isFinal())
                ->url(
                    fn() =>
                    route('filament.admin.resources.voyages.index', [
                        'tableFilters[vessel_plan_id][value]' => $this->record->id,
                    ])
                ),

            DeleteAction::make()
                ->visible(fn() => $this->record->isDraft()),
        ];
    }
}
