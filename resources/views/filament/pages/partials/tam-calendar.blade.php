<div class="bg-white rounded-2xl shadow-sm border overflow-hidden">

    <div class="px-4 py-3 border-b font-semibold text-sm">
        Kalender Jadwal Pelayaran — {{ $calendar['month_label'] }}
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-[1400px] w-full border-collapse text-[11px]">

            <thead>
                <tr>
                    <th class="sticky left-0 bg-gray-100 border px-3 py-2 w-40 text-left z-10">
                        Lane
                    </th>

                    @foreach ($calendar['days'] as $day)
                        <th class="border px-1 py-2 text-center w-12
                            {{ $day['isWeekend'] ? 'bg-rose-50 text-rose-600' : 'bg-gray-50' }}">
                            <div class="text-[9px] uppercase tracking-wide">
                                {{ $day['dow'] }}
                            </div>
                            <div class="font-semibold">
                                {{ $day['n'] }}
                            </div>
                        </th>
                    @endforeach
                </tr>
            </thead>

            <tbody>
                @foreach ($calendar['lanes'] as $laneKey => $laneLabel)

                    <tr>
                        <td class="sticky left-0 bg-white border px-3 py-2 font-semibold z-10">
                            {{ $laneLabel }}
                        </td>

                        @for ($d = 1; $d <= $calendar['days_count']; $d++)
                            <td class="border px-1 py-1 align-top">

                                @if (!empty($calendar['bucket'][$laneKey][$d]))
                                    @foreach ($calendar['bucket'][$laneKey][$d] as $chip)
                                        <div class="mb-1 rounded-md text-[10px] px-2 py-1 truncate font-medium text-white {{ $chip['color'] }}">
                                            {{ $chip['short'] }}
                                            <div class="text-[9px] opacity-90">
                                                {{ $chip['voyage_no'] }}
                                            </div>
                                        </div>
                                    @endforeach
                                @else
                                    <span class="text-gray-300">—</span>
                                @endif

                            </td>
                        @endfor

                    </tr>

                @endforeach
            </tbody>

        </table>
    </div>

    <div class="px-4 py-3 border-t bg-gray-50 text-xs flex gap-4 items-center">
        <span class="font-semibold text-gray-600">Keterangan:</span>

        <span class="flex items-center gap-1">
            <span class="w-3 h-3 bg-emerald-700 rounded"></span>
            Selesai
        </span>

        <span class="flex items-center gap-1">
            <span class="w-3 h-3 bg-blue-700 rounded"></span>
            Berlayar
        </span>

        <span class="flex items-center gap-1">
            <span class="w-3 h-3 bg-red-700 rounded"></span>
            Terlambat
        </span>

        <span class="flex items-center gap-1">
            <span class="w-3 h-3 bg-gray-700 rounded"></span>
            Terjadwal
        </span>
    </div>

</div>