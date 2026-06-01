<?php

namespace App\Filament\Resources\VoyageResource\Pages;

use App\Enums\VoyageRegistryStatus;
use App\Filament\Resources\VoyageResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListVoyages extends ListRecords
{
    protected static string $resource = VoyageResource::class;

    public function getTitle(): string
    {
        return 'Registry Voyage';
    }

    public function getSubheading(): ?string
    {
        return 'Data operasional dan pergerakan voyage';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Tambah Voyage'),
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
