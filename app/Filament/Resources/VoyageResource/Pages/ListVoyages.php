<?php

namespace App\Filament\Resources\VoyageResource\Pages;

use App\Filament\Resources\VoyageResource;
use App\Filament\Resources\VoyageResource\Widgets\VoyageStats;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListVoyages extends ListRecords
{
    protected static string $resource = VoyageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Tambah Voyage'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            VoyageStats::class,
        ];
    }

    public function updatedTableFilters(): void
    {
        $period = data_get($this->tableFilters, 'period_month.value');

        $this->dispatch('voyage-period-updated', period: $period);
    }
}
