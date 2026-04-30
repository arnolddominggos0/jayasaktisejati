<?php

namespace App\Filament\Resources\BriefingSessionResource\Pages;

use App\Filament\Resources\BriefingSessionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBriefingSessions extends ListRecords
{
    protected static string $resource = BriefingSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
