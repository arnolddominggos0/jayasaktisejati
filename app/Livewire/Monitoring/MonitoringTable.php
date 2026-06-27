<?php

namespace App\Livewire\Monitoring;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Component;

class MonitoringTable extends Component
{
    public ?LengthAwarePaginator $rows = null;

    public string $groupMode = 'flat';

    public ?int $selectedUnitId = null;

    public int $focusedRowIndex = 0;

    public function openDetail(int $unitId): void
    {
        $this->selectedUnitId = $unitId;
        $this->dispatch('open-unit-detail', unitId: $unitId);
    }

    public function handleKeyboard(string $key): void
    {
        // TODO Sprint 6.3: implement j/k/Enter/Esc
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.monitoring.monitoring-table');
    }
}