<?php

namespace App\Filament\Resources\DepotActivityResource\Pages;

use App\Filament\Resources\DepotActivityResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDepotActivities extends ListRecords
{
    protected static string $resource = DepotActivityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
