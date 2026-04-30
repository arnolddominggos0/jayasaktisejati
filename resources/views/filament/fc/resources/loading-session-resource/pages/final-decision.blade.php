<x-filament-panels::page>
    <x-filament-panels::form wire:submit="save">
        {{ $this->form }}

        <div class="flex gap-3">
            @foreach($this->getFormActions() as $action)
                {{ $action }}
            @endforeach
        </div>
    </x-filament-panels::form>

    {{-- Decision Summary --}}
    @php
        $suggestedStatus = $this->record->evaluateFinalDecision();
    @endphp

    <div class="mt-6 p-4 rounded-lg {{ $suggestedStatus->canProceed() ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200' }}">
        <h3 class="text-lg font-semibold mb-4 {{ $suggestedStatus->canProceed() ? 'text-green-800' : 'text-red-800' }}">
            Rekomendasi Sistem: {{ $suggestedStatus->label() }}
        </h3>

        @if($this->record->critical_issues_count > 0)
            <div class="mb-4 p-4 bg-red-100 border-l-4 border-red-500">
                <div class="font-semibold text-red-700 mb-2">Isu Kritis Ditemukan:</div>
                <ul class="list-disc list-inside text-red-600">
                    @foreach($this->record->findings()->critical()->get() as $finding)
                        <li>{{ $finding->item_name }}: {{ $finding->description }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid grid-cols-2 gap-4 text-sm">
            <div>
                <strong>Pilar Rack:</strong>
                <span class="{{ $this->record->rack_pillars_ok ? 'text-green-600' : 'text-red-600' }}">
                    {{ $this->record->rack_pillars_ok ? '✓ Aman' : '✗ Tidak Aman' }}
                </span>
            </div>
            <div>
                <strong>Drop Floor:</strong>
                <span class="{{ $this->record->drop_floor_ok ? 'text-green-600' : 'text-red-600' }}">
                    {{ $this->record->drop_floor_ok ? '✓ Aman' : '✗ Tidak Aman' }}
                </span>
            </div>
            <div>
                <strong>Peralatan:</strong>
                <span class="{{ $this->record->equipment_safe ? 'text-green-600' : 'text-red-600' }}">
                    {{ $this->record->equipment_safe ? '✓ Aman' : '✗ Tidak Aman' }}
                </span>
            </div>
            <div>
                <strong>Unit:</strong>
                <span class="{{ $this->record->unit_measurements_ok ? 'text-green-600' : 'text-red-600' }}">
                    {{ $this->record->unit_measurements_ok ? '✓ Aman' : '✗ Tidak Aman' }}
                </span>
            </div>
            <div>
                <strong>APD:</strong>
                <span class="{{ $this->record->apd_complete ? 'text-green-600' : 'text-red-600' }}">
                    {{ $this->record->apd_complete ? '✓ Lengkap' : '✗ Tidak Lengkap' }}
                </span>
            </div>
            <div>
                <strong>Manpower:</strong>
                <span class="{{ $this->record->mp_sufficient ? 'text-green-600' : 'text-red-600' }}">
                    {{ $this->record->mp_sufficient ? '✓ Mencukupi' : '✗ Tidak Mencukupi' }}
                </span>
            </div>
        </div>
    </div>
</x-filament-panels::page>
