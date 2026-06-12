<?php

namespace App\Filament\FC\Resources\ContainerReadinessSessionResource\Pages;

use App\Filament\FC\Resources\ContainerReadinessSessionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListContainerReadinessSessions extends ListRecords
{
    protected static string $resource = ContainerReadinessSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('+ Input Container')->icon('heroicon-m-plus-circle'),
        ];
    }
}
