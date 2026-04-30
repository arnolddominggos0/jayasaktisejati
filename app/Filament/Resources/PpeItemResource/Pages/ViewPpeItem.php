<?php

namespace App\Filament\Resources\PpeItemResource\Pages;

use App\Filament\Resources\PpeItemResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPpeItem extends ViewRecord
{
    protected static string $resource = PpeItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
