<?php

namespace App\Livewire\Monitoring;

use Livewire\Component;

class MonitoringTable extends Component
{
    // Scalar props only — Livewire 3 cannot serialize LengthAwarePaginator.
    // Sprint 6.3B will wire these to a real computed paginator.
    public int $totalRows = 0;

    public int $perPage = 50;

    public int $currentPage = 1;

    public int $lastPage = 1;

    public string $groupMode = 'flat';

    public ?int $selectedUnitId = null;

    public int $focusedRowIndex = 0;

    public function mount(
        int $totalRows = 0,
        int $perPage = 50,
        int $currentPage = 1,
        int $lastPage = 1,
        string $groupMode = 'flat',
    ): void {
        $this->totalRows   = $totalRows;
        $this->perPage     = $perPage;
        $this->currentPage = $currentPage;
        $this->lastPage    = $lastPage;
        $this->groupMode   = $groupMode;
    }

    public function openDetail(int $unitId): void
    {
        $this->selectedUnitId = $unitId;
        $this->dispatch('open-unit-detail', unitId: $unitId);
    }

    public function handleKeyboard(string $key): void
    {
        // TODO Sprint 6.3B: implement j/k/Enter/Esc
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.monitoring.monitoring-table');
    }
}