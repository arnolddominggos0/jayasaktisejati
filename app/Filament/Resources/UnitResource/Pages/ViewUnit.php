<?php

namespace App\Filament\Resources\UnitResource\Pages;

use App\Filament\Resources\UnitResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewUnit extends ViewRecord
{
    protected static string $resource = UnitResource::class;

    protected static string $view = 'filament.resources.unit-resource.pages.view-unit';

    public function mount(int|string $record): void
    {
        parent::mount($record);

        $this->record->loadMissing([
            'shipment',
            'inspections.items',
            'unitChecks',
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

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('← Kembali')
                ->url(UnitResource::getUrl('index'))
                ->color('gray'),
        ];
    }
}
