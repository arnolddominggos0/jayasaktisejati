<?php

namespace App\Filament\FC\Resources\BriefingSessionResource\Pages;

use App\Filament\FC\Resources\BriefingSessionResource;
use Filament\Resources\Pages\EditRecord;

class EditBriefingSession extends EditRecord
{
    protected static string $resource = BriefingSessionResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}
