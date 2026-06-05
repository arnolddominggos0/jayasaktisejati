<div class="bg-white border border-gray-200/40 rounded-lg overflow-hidden">

    <div class="px-3 py-1.5 border-b border-gray-100/60 flex justify-between items-center">
        <div class="text-[10px] tracking-wide text-gray-500 font-medium">
            Kalender Operasional — {{ $calendar['month_label'] }}
        </div>

        <div class="text-[10px] text-gray-400">
            Merah = weekend
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-[1000px] w-full border-collapse text-[10px]">

            <thead class="sticky top-0 z-30 bg-white">
                <tr class="bg-gray-50/50">
                    <th class="sticky left-0 z-20 bg-white border-r border-gray-100/60 px-2 py-1.5 w-24 text-left font-medium text-gray-500 text-[10px]">
                        Lane
                    </th>

                    @foreach ($calendar['days'] as $day)
                        <th wire:key="cal-hd-{{ $day['n'] }}"
                            class="px-1 py-1 text-center border-r border-gray-100/30
                            {{ $day['isWeekend'] ? 'bg-rose-50/50 text-rose-400' : 'text-gray-500' }}
                            {{ $day['isToday'] ? 'bg-blue-50/50 border-b border-blue-400' : '' }}">
                            <div class="text-[8px] uppercase tracking-wide">{{ $day['dow'] }}</div>
                            <div class="font-semibold text-[10px]">{{ $day['n'] }}</div>
                        </th>
                    @endforeach
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-50/60">
                @foreach ($calendar['lanes'] as $laneKey => $laneLabel)
                    <tr wire:key="cal-lane-{{ $laneKey }}" class="align-top">
                        <td class="sticky left-0 z-10 bg-white border-r border-gray-100/60 px-2 py-1.5 font-medium text-gray-500 text-[10px]">
                            {{ $laneLabel }}
                        </td>

                        @for ($i = 1; $i <= $calendar['days_count']; $i++)
                            <td wire:key="cal-{{ $laneKey }}-{{ $i }}" class="border-r border-gray-100/20 p-0.5 h-12 align-top">
                                @foreach ($calendar['bucket'][$laneKey][$i] as $chipIdx => $chip)
                                    @php
                                        $severity = $chip['severity'] ?? null;
                                        $severityBorder = match ($severity) {
                                            'minor'    => 'ring-1 ring-yellow-400/60',
                                            'moderate' => 'ring-1 ring-orange-400/60',
                                            'major'    => 'ring-1 ring-red-500/60',
                                            default    => '',
                                        };
                                    @endphp

                                    <div wire:key="cal-chip-{{ $laneKey }}-{{ $i }}-{{ $chip['voyage_id'] }}"
                                        class="mb-0.5 rounded-sm px-1 py-0.5 text-[9px] font-medium shadow-sm {{ $chip['status_color'] }} {{ $severityBorder }}">
                                        <div class="truncate text-[9px] font-semibold">{{ $chip['vessel'] }}</div>
                                        <div class="text-[8px] opacity-80">{{ $chip['voyage_no'] }}</div>
                                        @if ($chip['delay_label'])
                                            <div class="text-[8px] font-semibold mt-0.5">{{ $chip['delay_label'] }}</div>
                                        @endif
                                    </div>
                                @endforeach
                            </td>
                        @endfor
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="px-3 py-1.5 border-t border-gray-100/60 text-[10px] flex flex-wrap gap-3 text-gray-500">
        <x-operational.status-dot color="bg-emerald-500" label="Selesai" />
        <x-operational.status-dot color="bg-blue-500" label="Berlayar" />
        <x-operational.status-dot color="bg-red-500" label="Terlambat" />
        <x-operational.status-dot color="bg-gray-400" label="Terjadwal" />
        <x-operational.status-dot color="bg-yellow-400" label="≤1 Hari" />
        <x-operational.status-dot color="bg-orange-400" label="≤3 Hari" />
        <x-operational.status-dot color="bg-red-600" label="&gt;3 Hari" />
    </div>
</div>
