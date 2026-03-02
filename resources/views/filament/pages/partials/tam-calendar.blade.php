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
        <table class="min-w-[1600px] w-full border-collapse text-xs">

            <thead>
                <tr class="bg-gray-50">
                    <th class="sticky left-0 z-20 bg-white border-r px-4 py-3 w-44 text-left font-semibold">
                        Lane
                    </th>

                    @foreach ($calendar['days'] as $day)
                        <th
                            class="px-2 py-2 text-center border-r
                            {{ $day['isWeekend'] ? 'bg-rose-50 text-rose-500' : 'text-gray-700' }}
                            {{ $day['isToday'] ? 'border-b-2 border-blue-600' : '' }}">

                            <div class="text-[10px] uppercase">
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

                        <td class="sticky left-0 z-10 bg-white border-r px-4 py-4 font-medium text-gray-700">
                            {{ $laneLabel }}
                        </td>

                        @for ($i = 1; $i <= $calendar['days_count']; $i++)
                            <td class="border-r p-2 h-28 align-top">

                                @foreach ($calendar['bucket'][$laneKey][$i] as $chip)
                                    @php
                                        $status = $chip['status'];
                                    @endphp

                                    <div
                                        class="mb-2 rounded-lg px-3 py-2 text-xs font-semibold shadow-sm {{ $status->color() }}">
                                        {{ $chip['vessel'] }}
                                        <div class="text-[10px] opacity-80">
                                            {{ $chip['voyage_no'] }}
                                        </div>

                                        @if ($status === \App\Enums\VoyageOperationalStatus::DELAYED)
                                            <div class="text-[9px] font-bold text-red-600 mt-1">
                                                DELAY
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

    <div class="px-6 py-3 border-t text-xs flex gap-6 text-gray-600">
        <div class="flex items-center gap-2">
            <span class="w-3 h-3 bg-green-600 rounded-full"></span> Selesai
        </div>
        <div class="flex items-center gap-2">
            <span class="w-3 h-3 bg-blue-600 rounded-full"></span> Berlayar
        </div>
        <div class="flex items-center gap-2">
            <span class="w-3 h-3 bg-red-600 rounded-full"></span> Terlambat
        </div>
        <div class="flex items-center gap-2">
            <span class="w-3 h-3 bg-gray-500 rounded-full"></span> Terjadwal
        </div>
    </div>

</div>
