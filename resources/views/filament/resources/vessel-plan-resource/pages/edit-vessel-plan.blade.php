<x-filament-panels::page>
    @php
        $analysis  = $record->analyze();
        $total     = $record->items()->count();
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
</x-filament-panels::page>
