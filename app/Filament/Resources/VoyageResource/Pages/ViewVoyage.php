<?php

namespace App\Filament\Resources\VoyageResource\Pages;

use App\Filament\Resources\VoyageResource;
use Filament\Resources\Pages\ViewRecord;

class ViewVoyage extends ViewRecord
{
    protected static string $resource = VoyageResource::class;

    protected static string $view = 'filament.resources.voyage-resource.pages.view-voyage';

    public function mount(int|string $record): void
    {
        parent::mount($record);

        $this->record->load([
            'vessel',
            'pol',
            'pod',
            'shippingLine',
            'milestones',
            'checkpoints',
            'vesselChecks',
            'delayLogs',
        ]);
    }

    public function getHeading(): string
    {
        return '';
    }

    public function getSubheading(): ?string
    {
        return null;
    }
}
