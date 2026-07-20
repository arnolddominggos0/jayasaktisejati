<?php

namespace App\Livewire\Monitoring;

use App\Services\Monitoring\DetailUnitProvider;
use App\ViewModels\Monitoring\UnitDetailData;
use Filament\Notifications\Notification;
use Livewire\Attributes\On;
use Livewire\Component;

class MonitoringDetailSlide extends Component
{
    // Protected: Livewire cannot serialize this readonly ViewModel.
    protected ?UnitDetailData $unitDetail = null;

    public ?int $unitId = null;
    public bool $shipmentNotFound = false;

    #[On('open-unit-detail')]
    public function load(int $unitId): void
    {
        $this->unitId = $unitId;
        $this->unitDetail = null;
        $this->shipmentNotFound = false;

        try {
            $result = app(DetailUnitProvider::class)->provide($unitId);
            if ($result->unit_id === 0) {
                $this->shipmentNotFound = true;
            } else {
                $this->unitDetail = $result;
            }
        } catch (\Throwable $e) {
            Notification::make()
                ->danger()
                ->title('Gagal memuat detail')
                ->send();
            $this->shipmentNotFound = true;
        }

        $this->dispatch('detail-loaded');
    }

    #[On('close-detail')]
    public function closeDetail(): void
    {
        $this->unitId = null;
        $this->unitDetail = null;
        $this->shipmentNotFound = false;
        $this->dispatch('detail-closed');
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.monitoring.monitoring-detail-slide', [
            'unitDetail'       => $this->unitDetail,
            'shipmentNotFound' => $this->shipmentNotFound,
        ]);
    }
}