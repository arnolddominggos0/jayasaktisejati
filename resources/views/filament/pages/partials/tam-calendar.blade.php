<div class="bg-white rounded-2xl border shadow-sm overflow-hidden">

    <div class="px-6 py-4 border-b flex justify-between items-center">
        <div class="font-semibold text-sm tracking-wide">
            Kalender Jadwal Pelayaran — {{ $calendar['month_label'] }}
        </div>
        <div class="text-xs text-gray-500">
            Hari merah = weekend
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-[1700px] w-full border-collapse text-xs">

            <thead>
                <tr class="bg-gray-50">
                    <th class="sticky left-0 z-20 bg-white border-r px-4 py-3 w-48 text-left font-semibold">
                        Lane
                    </th>

                    @foreach ($calendar['days'] as $day)
                        <th
                            class="px-2 py-3 text-center border-r
                            {{ $day['isWeekend'] ? 'bg-rose-50 text-rose-500' : 'text-gray-700' }}
                            {{ $day['isToday'] ? 'bg-blue-50 border-b-2 border-blue-600' : '' }}">

                            <div class="text-[10px] uppercase tracking-wide">
                                {{ $day['dow'] }}
                            </div>

                            <div class="font-semibold">
                                {{ $day['n'] }}
                            </div>
                        </th>
                    @endforeach
                </tr>
            </thead>

            <tbody class="divide-y">

                @foreach ($calendar['lanes'] as $laneKey => $laneLabel)
                    <tr class="align-top">

                        <td class="sticky left-0 z-10 bg-white border-r px-4 py-5 font-medium text-gray-700">
                            {{ $laneLabel }}
                        </td>

                        @for ($i = 1; $i <= $calendar['days_count']; $i++)
                            <td class="border-r p-3 h-32 align-top">

                                @foreach ($calendar['bucket'][$laneKey][$i] as $chip)
                                    @php
                                        $status = $chip['status'];
                                        $delayLabel = $chip['delay_label'] ?? null;
                                        $severity = $chip['severity'] ?? null;

                                        $severityBorder = match ($severity) {
                                            'minor' => 'ring-2 ring-yellow-400',
                                            'moderate' => 'ring-2 ring-orange-400',
                                            'major' => 'ring-2 ring-red-500',
                                            default => '',
                                        };
                                    @endphp

                                    <div
                                        class="mb-3 rounded-xl px-3 py-3 text-xs font-semibold
                                               shadow-sm hover:shadow-md transition-all duration-200
                                               {{ $status->color() }}
                                               {{ $severityBorder }}">

                                        <div class="truncate text-sm font-bold">
                                            {{ $chip['vessel'] }}
                                        </div>

                                        <div class="text-[11px] opacity-90 mt-1">
                                            {{ $chip['voyage_no'] }}
                                        </div>

                                        @if ($delayLabel)
                                            <div class="text-[10px] font-bold mt-2 tracking-wide">
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

    <div class="px-6 py-4 border-t text-xs flex flex-wrap gap-8 text-gray-600">

        <div class="flex items-center gap-2">
            <span class="w-3 h-3 bg-green-600 rounded-full"></span>
            Selesai
        </div>

        <div class="flex items-center gap-2">
            <span class="w-3 h-3 bg-blue-600 rounded-full"></span>
            Berlayar
        </div>

        <div class="flex items-center gap-2">
            <span class="w-3 h-3 bg-red-600 rounded-full"></span>
            Terlambat
        </div>

        <div class="flex items-center gap-2">
            <span class="w-3 h-3 bg-gray-600 rounded-full"></span>
            Terjadwal
        </div>

        <div class="flex items-center gap-2">
            <span class="w-3 h-3 bg-yellow-400 rounded-full"></span>
            Terlambat Ringan (≤30 menit)
        </div>

        <div class="flex items-center gap-2">
            <span class="w-3 h-3 bg-orange-400 rounded-full"></span>
            Terlambat Sedang (≤2 jam)
        </div>

        <div class="flex items-center gap-2">
            <span class="w-3 h-3 bg-red-500 rounded-full"></span>
            Terlambat Berat (&gt;2 jam)
        </div>

    </div>

</div>