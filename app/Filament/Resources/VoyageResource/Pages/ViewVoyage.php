<?php

namespace App\Filament\Resources\VoyageResource\Pages;

use App\Filament\Resources\VoyageResource;
use App\Filament\Resources\VoyageResource\Widgets\VoyageDelayHistoryWidget;
use App\Filament\Resources\VoyageResource\Widgets\VoyageKpiWidget;
use App\Filament\Resources\VoyageResource\Widgets\VoyageMilestoneWidget;
use App\Filament\Resources\VoyageResource\Widgets\VoyageReadinessWidget;
use App\Filament\Resources\VoyageResource\Widgets\VoyageTimelineWidget;
use Filament\Resources\Pages\ViewRecord;

class ViewVoyage extends ViewRecord
{
    protected static string $resource = VoyageResource::class;

    public function getHeading(): string
    {
        return 'Operational Detail Sheet';
    }

    public function getSubheading(): ?string
    {
        return 'Detail voyage, audit trail & lifecycle — untuk monitoring harian gunakan Monitoring Vessel';
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('monitoring')
                ->label('Kembali ke Monitoring')
                ->url(fn() => \App\Filament\Pages\MonitoringKapalTam::getUrl())
                ->icon('heroicon-o-arrow-left')
                ->color('gray'),
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            VoyageKpiWidget::class,
            VoyageTimelineWidget::class,
            VoyageMilestoneWidget::class,
            VoyageReadinessWidget::class,
            VoyageDelayHistoryWidget::class,
        ];
    }
}
