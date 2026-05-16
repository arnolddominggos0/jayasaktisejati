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
                        <th
                            class="px-1 py-1 text-center border-r border-gray-100/30
                            {{ $day['isWeekend'] ? 'bg-rose-50/50 text-rose-400' : 'text-gray-500' }}
                            {{ $day['isToday'] ? 'bg-blue-50/50 border-b border-blue-400' : '' }}">

                            <div class="text-[8px] uppercase tracking-wide">
                                {{ $day['dow'] }}
                            </div>

                            <div class="font-semibold text-[10px]">
                                {{ $day['n'] }}
                            </div>
                        </th>
                    @endforeach

                </tr>
            </thead>

            <tbody class="divide-y divide-gray-50/60">

                @foreach ($calendar['lanes'] as $laneKey => $laneLabel)

                    <tr class="align-top">

                        <td class="sticky left-0 z-10 bg-white border-r border-gray-100/60 px-2 py-1.5 font-medium text-gray-500 text-[10px]">
                            {{ $laneLabel }}
                        </td>

                        @for ($i = 1; $i <= $calendar['days_count']; $i++)
                            <td class="border-r border-gray-100/20 p-0.5 h-12 align-top">

                                @foreach ($calendar['bucket'][$laneKey][$i] as $chip)
                                    @php
                                        $status = $chip['status'];
                                        $delayLabel = $chip['delay_label'] ?? null;
                                        $severity = $chip['severity'] ?? null;

                                        $severityBorder = match ($severity) {
                                            'minor' => 'ring-1 ring-yellow-400/60',
                                            'moderate' => 'ring-1 ring-orange-400/60',
                                            'major' => 'ring-1 ring-red-500/60',
                                            default => '',
                                        };
                                    @endphp

                                    <div
                                        class="mb-0.5 rounded-sm px-1 py-0.5 text-[9px] font-medium
                                               shadow-sm
                                               {{ $status->color() }}
                                               {{ $severityBorder }}">

                                        <div class="truncate text-[9px] font-semibold">
                                            {{ $chip['vessel'] }}
                                        </div>

                                        <div class="text-[8px] opacity-80">
                                            {{ $chip['voyage_no'] }}
                                        </div>

                                        @if ($delayLabel)
                                            <div class="text-[8px] font-semibold mt-0.5">
                                                {{ $delayLabel }}
                                            </div>
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

        <div class="flex items-center gap-1">
            <span class="w-1.5 h-1.5 bg-green-500 rounded-full"></span>
            Selesai
        </div>

        <div class="flex items-center gap-1">
            <span class="w-1.5 h-1.5 bg-blue-500 rounded-full"></span>
            Berlayar
        </div>

        <div class="flex items-center gap-1">
            <span class="w-1.5 h-1.5 bg-red-500 rounded-full"></span>
            Terlambat
        </div>

        <div class="flex items-center gap-1">
            <span class="w-1.5 h-1.5 bg-gray-400 rounded-full"></span>
            Terjadwal
        </div>

        <div class="flex items-center gap-1">
            <span class="w-1.5 h-1.5 bg-yellow-400 rounded-full"></span>
            ≤1 Hari
        </div>

        <div class="flex items-center gap-1">
            <span class="w-1.5 h-1.5 bg-orange-400 rounded-full"></span>
            ≤3 Hari
        </div>

        <div class="flex items-center gap-1">
            <span class="w-1.5 h-1.5 bg-red-600 rounded-full"></span>
            &gt;3 Hari
        </div>

    </div>
</div>
