<?php

namespace App\Filament\Resources\PpeAssignmentResource\Pages;

use App\Filament\Resources\PpeAssignmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPpeAssignments extends ListRecords
{
    protected static string $resource = PpeAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
