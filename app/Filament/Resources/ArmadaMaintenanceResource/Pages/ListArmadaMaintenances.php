<?php

namespace App\Filament\Resources\ArmadaMaintenanceResource\Pages;

use App\Filament\Resources\ArmadaMaintenanceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListArmadaMaintenances extends ListRecords
{
    protected static string $resource = ArmadaMaintenanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Buka Tiket'),
        ];
    }
}
