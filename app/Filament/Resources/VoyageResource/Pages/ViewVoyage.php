<?php

namespace App\Filament\Resources\VoyageResource\Pages;

use App\Filament\Resources\VoyageResource;
use Filament\Resources\Pages\ViewRecord;

class ViewVoyage extends ViewRecord
{
    protected static string $resource = VoyageResource::class;

    public function getHeading(): string
    {
        return 'Detail Pelayaran';
    }
}
