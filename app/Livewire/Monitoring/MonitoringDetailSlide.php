<?php

namespace App\Livewire\Monitoring;

use App\Services\Monitoring\DetailUnitProvider;
use App\ViewModels\Monitoring\UnitDetailData;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Livewire\Attributes\On;
use Livewire\Component;

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
            $this->mountAction('viewDetail');
        } catch (\Throwable $e) {
            Notification::make()
                ->danger()
                ->title('Gagal memuat detail')
                ->send();
        }
    }

    public function viewDetailAction(): Action
    {
        return Action::make('viewDetail')
            ->slideOver()
            ->modalWidth('max-w-3xl')
            ->modalHeading(fn() => $this->unitDetail?->unit_reg_no ?? 'Detail Unit')
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Tutup')
            ->content(fn() => view('livewire.monitoring.monitoring-detail-slide', [
                'unitDetail' => $this->unitDetail,
            ]));
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.monitoring.monitoring-detail-slide', [
            'unitDetail' => $this->unitDetail,
        ]);
    }
}