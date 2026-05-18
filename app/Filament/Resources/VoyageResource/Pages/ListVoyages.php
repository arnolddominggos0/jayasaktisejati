<?php

namespace App\Filament\Resources\VoyageResource\Pages;

use App\Enums\VoyageRegistryStatus;
use App\Filament\Resources\VoyageResource;
use App\Filament\Resources\VoyageResource\Widgets\VoyageRegistrySummaryStrip;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListVoyages extends ListRecords
{
    protected static string $resource = VoyageResource::class;

    public function getTitle(): string
    {
        return 'Voyage Registry';
    }

    public function getSubheading(): ?string
    {
        return 'Fleet movement registry & administrative lifecycle';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Tambah Voyage'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            VoyageRegistrySummaryStrip::class,
        ];
    }

    protected function getTableQuery(): Builder
    {
        $query = parent::getTableQuery();

        $filters = $this->tableFilters ?? [];
        $includeArchived = data_get($filters, 'include_archived.value', false);

        if (! in_array($includeArchived, [true, '1', 1], true)) {
            $query->where('registry_status', '!=', VoyageRegistryStatus::ARCHIVED->value);
        }

        return $query;
    }
}