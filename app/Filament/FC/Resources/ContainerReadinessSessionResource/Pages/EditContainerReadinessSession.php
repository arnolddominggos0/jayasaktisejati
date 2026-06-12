<?php

namespace App\Filament\FC\Resources\ContainerReadinessSessionResource\Pages;

use App\Filament\FC\Pages\MpReadinessMonitoring;
use App\Filament\FC\Resources\ContainerReadinessSessionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditContainerReadinessSession extends EditRecord
{
    protected static string $resource = ContainerReadinessSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }

    /** Setelah simpan → kembali ke Monitoring Operasional */
    protected function getRedirectUrl(): string
    {
        return MpReadinessMonitoring::getUrl();
    }
}
