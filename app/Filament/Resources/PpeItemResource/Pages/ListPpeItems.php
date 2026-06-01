<?php

namespace App\Filament\Resources\PpeItemResource\Pages;

use App\Filament\Resources\PpeItemResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPpeItems extends ListRecords
{
    protected static string $resource = PpeItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
