<?php

namespace App\Filament\Resources\VesselCheckResource\Pages;

use App\Filament\Resources\VesselCheckResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditVesselCheck extends EditRecord
{
    protected static string $resource = VesselCheckResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
