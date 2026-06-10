<?php

namespace App\Filament\FC\Resources\ContainerReadinessSessionResource\Pages;

use App\Filament\FC\Pages\MpReadinessMonitoring;
use App\Filament\FC\Resources\ContainerReadinessSessionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateContainerReadinessSession extends CreateRecord
{
    protected static string $resource = ContainerReadinessSessionResource::class;

    /** Setelah simpan → kembali ke Monitoring Operasional */
    protected function getRedirectUrl(): string
    {
        return MpReadinessMonitoring::getUrl();
    }
}
