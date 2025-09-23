<?php

namespace App\Filament\Resources\ArmadaMaintenanceResource\Pages;

use App\Filament\Resources\ArmadaMaintenanceResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewArmadaMaintenance extends ViewRecord
{
    protected static string $resource = ArmadaMaintenanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()->label('Ubah'),
            Actions\DeleteAction::make()->label('Hapus'),
        ];
    }
}
