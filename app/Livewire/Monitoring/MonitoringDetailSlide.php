<?php

namespace App\Livewire\Monitoring;

use App\Services\Monitoring\DetailUnitProvider;
use App\ViewModels\Monitoring\UnitDetailData;
use Filament\Notifications\Notification;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Hotfix: this component used to call $this->mountAction('viewDetail') /
 * $this->unmountAction(), but never implemented Filament's HasActions
 * contract or used the InteractsWithActions trait — both undefined-method
 * calls, throwing on every single open AND close. The try/catch around
 * load() silently swallowed it as a generic "Gagal memuat detail" toast,
 * masking that $this->unitDetail had actually already loaded correctly one
 * line above. The panel's real visibility is the plain `@if ($unitDetail)`
 * in the Blade view (no slide-over CSS/modal exists) — the mountAction
 * call was never load-bearing for anything actually rendered, so it (and
 * the dead viewDetailAction() method) were removed rather than wiring up
 * the full Actions infrastructure for a feature that isn't used.
 */
class MonitoringDetailSlide extends Component
{
    // Protected to avoid Livewire serialization of complex readonly ViewModel
    protected ?UnitDetailData $unitDetail = null;

    public ?int $unitId = null;

    #[On('open-unit-detail')]
    public function load(int $unitId): void
    {
        $this->unitId = $unitId;

        try {
            $this->unitDetail = app(DetailUnitProvider::class)->provide($unitId);
        } catch (\Throwable $e) {
            Notification::make()
                ->danger()
                ->title('Gagal memuat detail')
                ->send();
        }
    }

    #[On('close-detail')]
    public function closeDetail(): void
    {
        $this->unitId = null;
        $this->dispatch('detail-closed');
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.monitoring.monitoring-detail-slide', [
            'unitDetail' => $this->unitDetail,
        ]);
    }
}