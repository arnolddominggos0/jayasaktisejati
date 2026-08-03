<?php

namespace App\Filament\Resources\VesselPlanResource\Pages;

use App\Filament\Resources\VesselPlanResource;
use App\Models\VesselPlan;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;

class ListVesselPlans extends ListRecords
{
    protected static string $resource = VesselPlanResource::class;

    protected static string $view = 'filament.resources.vessel-plan-resource.pages.list-vessel-plans';

    #[Url]
    public ?string $year = null;

    public function mount(): void
    {
        parent::mount();

        if (blank($this->year)) {
            $this->year = (string) now()->year;
        }
    }

    protected function getTableQuery(): ?Builder
    {
        return parent::getTableQuery()?->when(
            filled($this->year),
            fn(Builder $query) => $query->whereYear('period_month', $this->year)
        );
    }

    /**
     * @return array<string, string>
     */
    public function getYearOptions(): array
    {
        return VesselPlan::query()
            ->pluck('period_month')
            ->map(fn($date) => (string) $date->year)
            ->push((string) now()->year)
            ->when(filled($this->year), fn($years) => $years->push((string) $this->year))
            ->unique()
            ->sortDesc()
            ->mapWithKeys(fn($year) => [$year => $year])
            ->all();
    }

    protected function hasRecordsInScope(): bool
    {
        return VesselPlan::query()
            ->when(
                filled($this->year),
                fn(Builder $query) => $query->whereYear('period_month', $this->year)
            )
            ->exists();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create')
                ->label('Tambah Vessel Plan')
                ->icon('heroicon-o-plus')
                ->url(static::getResource()::getUrl('create'))
                // Saat scope kosong, CTA tunggal ada di empty state tabel.
                ->visible(fn() => (auth_user()?->isSuperAdmin() ?? false) && $this->hasRecordsInScope()),
        ];
    }
}
