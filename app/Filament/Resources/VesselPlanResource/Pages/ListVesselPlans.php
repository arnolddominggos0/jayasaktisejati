<?php

namespace App\Filament\Resources\VesselPlanResource\Pages;

use App\Filament\Resources\VesselPlanResource;
use App\Models\VesselPlan;
use App\Supports\MonthParam;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;

class ListVesselPlans extends ListRecords
{
    protected static string $resource = VesselPlanResource::class;

    // Custom view (bukan bawaan Filament) — supaya dropdown Tahun bisa
    // dirender sebagai context selector polos tepat di bawah judul, bukan
    // lewat panel "Filter" native Filament (yang selalu membawa trigger
    // "Filter", "Filter Aktif", dan "Reset").
    protected static string $view = 'filament.resources.vessel-plan-resource.pages.list-vessel-plans';

    // Tahun = context halaman ("saya sedang melihat tahun berapa"), bukan
    // advanced filter — persist di URL seperti navigasi biasa (?year=2026).
    #[Url]
    public ?string $year = null;

    public function mount(): void
    {
        parent::mount();

        if (blank($this->year)) {
            $this->year = (string) now()->year;
        }
    }

    /**
     * Query tetap whereYear('period_month', ...) — logika yang sama persis
     * dengan filter sebelumnya, hanya dipindah dari Filament filter closure
     * ke sini karena UI-nya sekarang context selector, bukan filter panel.
     */
    protected function getTableQuery(): ?Builder
    {
        return parent::getTableQuery()?->when(
            filled($this->year),
            fn (Builder $query) => $query->whereYear('period_month', $this->year)
        );
    }

    /**
     * @return array<string, string>
     */
    public function getYearOptions(): array
    {
        return VesselPlan::query()
            ->pluck('period_month')
            ->map(fn ($date) => $date->year)
            ->unique()
            ->sortDesc()
            ->mapWithKeys(fn ($year) => [(string) $year => (string) $year])
            ->all();
    }

    protected function getHeaderActions(): array
    {
        $month = MonthParam::resolve(request('month'));

        return [
            Action::make('generate')
                ->label("Generate Vessel Plan {$month['label']}")
                ->icon('heroicon-o-plus')
                ->visible(fn () =>
                    ! VesselPlan::where('period_month', $month['start'])->exists()
                )
                ->action(fn () =>
                    VesselPlan::generateForMonth($month['start'])
                ),
        ];
    }
}
