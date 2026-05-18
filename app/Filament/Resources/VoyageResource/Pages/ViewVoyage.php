<?php

namespace App\Filament\Resources\VoyageResource\Pages;

use App\Filament\Resources\VoyageResource;
use Filament\Resources\Pages\ViewRecord;

class ViewVoyage extends ViewRecord
{
    protected static string $resource = VoyageResource::class;

    protected static string $view = 'filament.resources.voyage-resource.pages.view-voyage';

    public function mount(int|string $record): void
    {
        parent::mount($record);

        $this->record->load([
            'vessel',
            'pol',
            'pod',
            'shippingLine',
            'milestones',
            'checkpoints',
            'vesselChecks',
            'delayLogs',
        ]);
    }

    public function getHeading(): string
    {
        return 'Lembar Eksekusi Operasional';
    }

    public function getSubheading(): ?string
    {
        return 'Detail operasional voyage — untuk monitoring harian gunakan Monitoring Voyage';
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('monitoring')
                ->label('Kembali ke Monitoring')
                ->url(fn () => \App\Filament\Pages\MonitoringKapalTam::getUrl())
                ->icon('heroicon-o-arrow-left')
                ->color('gray'),
        ];
    }
}
