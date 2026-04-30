<?php

namespace App\Filament\Resources\PpeAssignmentResource\Pages;

use App\Filament\Resources\PpeAssignmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPpeAssignment extends ViewRecord
{
    protected static string $resource = PpeAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
