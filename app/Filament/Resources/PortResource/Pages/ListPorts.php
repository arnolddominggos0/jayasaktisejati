<?php

namespace App\Filament\Resources\PortResource\Pages;

use App\Filament\Resources\PortResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;

class ListPorts extends ListRecords
{
    protected static string $resource = PortResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Tambah Pelabuhan'),
        ];
    }
}
