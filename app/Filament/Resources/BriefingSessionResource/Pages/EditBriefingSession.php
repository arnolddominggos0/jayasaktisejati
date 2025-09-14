<?php

namespace App\Filament\Resources\BriefingSessionResource\Pages;

use App\Filament\Resources\BriefingSessionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBriefingSession extends EditRecord
{
    protected static string $resource = BriefingSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
