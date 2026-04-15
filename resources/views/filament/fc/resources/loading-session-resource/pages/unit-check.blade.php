<x-filament-panels::page>
    <x-filament-panels::form wire:submit="save">
        {{ $this->form }}

        <div class="flex gap-3">
            @foreach($this->getFormActions() as $action)
                {{ $action }}
            @endforeach
        </div>
    </x-filament-panels::form>

    {{-- Measurement Validation Summary --}}
    <div class="mt-6 p-4 bg-gray-50 rounded-lg">
        <h3 class="text-lg font-semibold mb-4">Validasi Pengukuran</h3>
        <div class="grid grid-cols-4 gap-4">
            @foreach([
                'distance_front_rh' => 'Jarak Front RH',
                'distance_rear_rh' => 'Jarak Rear RH',
                'distance_back_door' => 'Jarak Back Door',
                'distance_rear_lh' => 'Jarak Rear LH',
                'distance_front_lh' => 'Jarak Front LH',
                'drop_floor_front_height' => 'Tinggi DF Depan',
                'drop_floor_rear_height' => 'Tinggi DF Belakang',
                'container_roof_distance' => 'Jarak Atap',
            ] as $field => $label)
                @php
                    $value = $this->data[$field] ?? null;
                    $defaultRanges = \App\Models\UnitCheck::getDefaultValidationRanges();
                    $range = $this->data['validation_ranges'][$field] ?? $defaultRanges[$field];
                    $valid = $value !== null && $value >= $range['min'] && $value <= $range['max'];
                @endphp
                <div class="p-3 bg-white rounded shadow">
                    <div class="text-sm text-gray-600">{{ $label }}</div>
                    <div class="font-medium {{ $valid ? 'text-green-600' : ($value ? 'text-red-600' : 'text-gray-400') }}">
                        {{ $value ? $value . ' cm' : 'Belum diisi' }}
                        @if($value)
                            <span class="text-xs text-gray-500">({{ $range['min'] }}-{{ $range['max'] }})</span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</x-filament-panels::page>
