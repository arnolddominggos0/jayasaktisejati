<x-filament-panels::page>
    @php
        $analysis  = $record->analyze();
        $items     = $record->items->sortBy('planned_etd');
        $total     = $items->count();
        $riskLevel = $analysis['risk_level'] ?? 'valid';

        $statusLabel = match (true) {
            $total < 2              => 'Data Belum Cukup',
            $riskLevel === 'warning'  => 'PERINGATAN',
            $riskLevel === 'critical' => 'KRITIS',
            default                 => 'VALID',
        };

        $statusColor = match ($riskLevel) {
            'warning'  => 'text-amber-600',
            'critical' => 'text-red-600',
            default    => 'text-green-600',
        };
    @endphp

    <x-vessel-plan.summary
        :total="$total"
        :maxGap="$analysis['max_gap'] ?? 0"
        :idealGap="6"
        :statusLabel="$statusLabel"
        :statusColor="$statusColor"
    />

    <div class="overflow-hidden rounded-xl border bg-white">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-600">
                <tr>
                    <th class="px-4 py-3 text-left">Nama Pelayaran</th>
                    <th class="px-4 py-3 text-left">Voyage</th>
                    <th class="px-4 py-3 text-left">ETD</th>
                    <th class="px-4 py-3 text-left">ETA</th>
                    <th class="px-4 py-3 text-center">Selisih ETD</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @foreach ($items as $index => $item)
                    @php
                        $gap = $analysis['gaps'][$item->id] ?? null;
                    @endphp

                    <tr>
                        <td class="px-4 py-3">{{ $item->shippingLine->name ?? '-' }}</td>
                        <td class="px-4 py-3 font-medium">{{ $item->voyage_no }}</td>
                        <td class="px-4 py-3">{{ $item->planned_etd?->format('d M Y') }}</td>
                        <td class="px-4 py-3">{{ $item->planned_eta?->format('d M Y') }}</td>
                        <td class="px-4 py-3 text-center">
                            {{ $gap === null ? '—' : $gap }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-filament-panels::page>
