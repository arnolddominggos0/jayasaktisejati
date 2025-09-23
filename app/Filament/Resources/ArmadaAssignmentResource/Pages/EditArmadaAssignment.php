<?php

namespace App\Filament\Resources\ArmadaAssignmentResource\Pages;

use App\Filament\Resources\ArmadaAssignmentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditArmadaAssignment extends EditRecord
{
    protected static string $resource = ArmadaAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
