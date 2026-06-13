<x-filament-panels::page>

    {{-- Unit Info Header --}}
    <x-filament::section>
        <x-slot name="heading">Informasi Unit</x-slot>

        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
            <div>
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Model</p>
                <p class="mt-1 font-semibold text-gray-900 dark:text-white">{{ $this->unit->model_no ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Chassis No</p>
                <p class="mt-1 font-mono font-bold text-gray-900 dark:text-white">{{ $this->unit->chassis_no ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Engine No</p>
                <p class="mt-1 font-mono text-gray-900 dark:text-white">{{ $this->unit->engine_no ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Warna</p>
                <p class="mt-1 text-gray-900 dark:text-white">{{ $this->unit->color ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">DO Number</p>
                <p class="mt-1 text-gray-900 dark:text-white">{{ $this->unit->do_number ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Stage</p>
                <p class="mt-1 font-medium text-primary-600 dark:text-primary-400">{{ $this->inspection->stage_label }}</p>
            </div>
        </div>

        @if ($this->isReadOnly)
            <div class="mt-4 p-3 rounded-lg bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 flex flex-wrap gap-x-6 gap-y-1 text-sm">
                <span class="text-gray-700 dark:text-gray-300">
                    Status:
                    <strong class="{{ $this->inspection->status === 'passed' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                        {{ strtoupper($this->inspection->status) }}
                    </strong>
                </span>
                <span class="text-gray-700 dark:text-gray-300">
                    Gate Decision:
                    <strong class="{{ match($this->inspection->gate_decision) {
                        'accept' => 'text-green-600 dark:text-green-400',
                        'allow_with_remark' => 'text-amber-600 dark:text-amber-400',
                        'return_to_pdc' => 'text-red-600 dark:text-red-400',
                        default => 'text-gray-600',
                    } }}">
                        {{ \App\Models\UnitInspection::GATE_LABELS[$this->inspection->gate_decision] ?? '—' }}
                    </strong>
                </span>
                @if ($this->inspection->checkedBy)
                    <span class="text-gray-700 dark:text-gray-300">
                        Pemeriksa: <strong>{{ $this->inspection->checkedBy->name }}</strong>
                    </span>
                @endif
                @if ($this->inspection->submitted_at)
                    <span class="text-gray-700 dark:text-gray-300">
                        Tanggal: <strong>{{ $this->inspection->submitted_at->format('d M Y H:i') }}</strong>
                    </span>
                @endif
            </div>
        @endif
    </x-filament::section>

    {{-- Inspection Form --}}
    <x-filament-panels::form wire:submit="submit">
        {{ $this->form }}

        @if (! $this->isReadOnly)
            <div class="flex justify-end pt-2">
                <x-filament::button type="submit" size="lg" color="primary" icon="heroicon-m-check-circle">
                    Submit Inspeksi
                </x-filament::button>
            </div>
        @endif
    </x-filament-panels::form>

    <x-filament-actions::modals />
</x-filament-panels::page>
