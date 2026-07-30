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
        $years = VesselPlan::query()
            ->pluck('period_month')
            ->map(fn($date) => (string) $date->year)
            ->unique()
            ->sortDesc();

        if ($years->isEmpty()) {
            return [
                (string) now()->year => (string) now()->year,
            ];
        }

        return $years
            ->mapWithKeys(fn($year) => [$year => $year])
            ->all();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create')
                ->label('Tambah Vessel Plan')
                ->icon('heroicon-o-plus')
                ->url(static::getResource()::getUrl('create'))
                ->visible(function () {
                    return auth_user()?->isSuperAdmin()
                        && VesselPlan::query()->exists();
                }),
        ];
    }
}
