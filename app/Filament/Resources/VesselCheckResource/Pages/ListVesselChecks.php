<?php

namespace App\Filament\Resources\VesselCheckResource\Pages;

use App\Filament\Resources\VesselCheckResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListVesselChecks extends ListRecords
{
    protected static string $resource = VesselCheckResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
