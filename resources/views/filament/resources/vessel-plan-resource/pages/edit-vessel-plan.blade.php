<x-filament-panels::resources.pages.edit-record>
 @php
 $analysis = $record->analyze();
 $total = $record->items()->count();
 $idealGap = 6;

 $statusLabel =
 $total < 2
 ? 'Data Belum Cukup'
 : ($analysis['ok']
 ? 'Sesuai SOP (ETD ≤ 6 hari)'
 : 'Melanggar SOP (ETD > 6 hari)');
 @endphp

 <x-vessel-plan.summary
 :total="$total"
 :maxGap="$analysis['max_gap'] ?? 0"
 :idealGap="$idealGap"
 :statusLabel="$statusLabel"
 />
</x-filament-panels::resources.pages.edit-record>
