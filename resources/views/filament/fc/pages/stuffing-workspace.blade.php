<x-filament-panels::page>
    {{--
        Stuffing Workspace — Sprint ST-01.
        Sengaja polos (tanpa custom design system) sesuai sprint:
        "Tidak ada redesign visual. Tidak ada perubahan kosmetik."
        Fokus: alur kerja benar, bukan tampilan.
    --}}

    <div class="mb-4">
        <label class="block text-sm font-medium mb-1">Pilih Shipment</label>
        <select wire:model.live="shipmentId" class="fi-select-input block w-full rounded-lg border-gray-300 dark:bg-gray-800 dark:border-gray-600">
            <option value="">— Pilih —</option>
            @foreach ($candidateShipments as $s)
                <option value="{{ $s->id }}">{{ $s->code }}</option>
            @endforeach
        </select>
    </div>

    @if (! $selectedShipment)
        <p class="text-sm text-gray-500">Pilih shipment untuk memulai Stuffing.</p>
    @else

        {{-- Sprint OPS-10 (Scope 5): pesan jelas & spesifik saat briefing
             harian belum Ready — ditampilkan TERPISAH dan LEBIH MENONJOL
             dari checklist precondition generik di bawahnya, karena ini
             biasanya langkah pertama yang harus diselesaikan sebelum
             precondition lain relevan. Sengaja tidak menyebut Shipment/Unit
             (instruksi eksplisit sprint). --}}
        @if ($briefingBlockReason = $this->getBriefingBlockReason())
            <div class="mb-4 flex items-start gap-2 rounded-lg border border-amber-300 bg-amber-50 p-4 dark:border-amber-900/50 dark:bg-amber-900/20">
                <x-heroicon-o-exclamation-triangle class="mt-0.5 h-5 w-5 shrink-0 text-amber-500 dark:text-amber-400" />
                <p class="text-sm font-semibold text-amber-800 dark:text-amber-300">
                    {{ $briefingBlockReason }}
                </p>
            </div>
        @endif

        {{-- Precondition gate — sesuai sprint: jika belum lengkap, JANGAN
             tampilkan container/unit sama sekali, tampilkan pesan tegas. --}}
        <div class="mb-4 rounded-lg border p-3 {{ $preconditions['ok'] ? 'border-green-300' : 'border-red-300' }}">
            <p class="font-semibold mb-2">
                {{ $preconditions['ok'] ? '✓ Siap melakukan Stuffing' : 'Belum dapat melakukan Stuffing' }}
            </p>
            <ul class="text-sm space-y-1">
                @foreach ($preconditions['checks'] as $check)
                    <li>{{ $check['ok'] ? '✓' : '✗' }} {{ $check['label'] }}</li>
                @endforeach
            </ul>
        </div>

        @if ($preconditions['ok'])

            <div class="mb-4 text-sm">
                <strong>Progress Shipment:</strong>
                {{ $summary['stuffed_units'] }} / {{ $summary['total_units'] }} unit
                — status: {{ $summary['state'] }}
            </div>

            @if ($containers->isEmpty())
                <p class="text-sm text-gray-500">Tidak ada container Siap Stuffing untuk shipment ini.</p>
            @else
                <div class="space-y-4">
                    @foreach ($containers as $container)
                        @php
                            $planned = $container->plannedUnitCount();
                            $stuffed = $container->stuffedUnitCount();
                        @endphp
                        <div class="rounded-lg border p-3">
                            <div class="flex items-center justify-between mb-2">
                                <div>
                                    <span class="font-semibold">{{ $container->container_no }}</span>
                                    <span class="text-xs text-gray-500">({{ $container->type?->label() }})</span>
                                </div>
                                <div class="text-sm">
                                    <x-filament::badge :color="$container->stuffing_status->color()">
                                        {{ $container->stuffing_status->label() }}
                                    </x-filament::badge>
                                    <span class="ml-2">{{ $stuffed }} / {{ $planned }} unit</span>
                                </div>
                            </div>

                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="text-left text-gray-500">
                                        <th class="py-1">Unit</th>
                                        <th class="py-1">Status</th>
                                        <th class="py-1">Stuffed At</th>
                                        <th class="py-1">Stuffed By</th>
                                        <th class="py-1"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($container->units as $unit)
                                        <tr class="border-t">
                                            <td class="py-1">{{ $unit->reg_no ?? $unit->chassis_no }}</td>
                                            <td class="py-1">
                                                <x-filament::badge :color="$unit->allocation_status->color()">
                                                    {{ $unit->allocation_status->label() }}
                                                </x-filament::badge>
                                            </td>
                                            <td class="py-1">{{ $unit->stuffed_at?->format('d M Y H:i') ?? '—' }}</td>
                                            <td class="py-1">{{ $unit->stuffedBy?->name ?? '—' }}</td>
                                            <td class="py-1 text-right">
                                                @if ($unit->allocation_status->value === 'stuffed')
                                                    <x-filament::button
                                                        size="xs"
                                                        color="gray"
                                                        wire:click="mountAction('unmarkUnitStuffed', { unit: {{ $unit->id }} })"
                                                    >
                                                        Batalkan
                                                    </x-filament::button>
                                                @else
                                                    <x-filament::button
                                                        size="xs"
                                                        wire:click="mountAction('markUnitStuffed', { unit: {{ $unit->id }} })"
                                                    >
                                                        Tandai Masuk Container
                                                    </x-filament::button>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endforeach
                </div>
            @endif
        @endif
    @endif

    <x-filament-actions::modals />
</x-filament-panels::page>
