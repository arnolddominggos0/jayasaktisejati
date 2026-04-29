<div class="bg-white rounded-2xl shadow-sm border overflow-hidden">
    <div class="px-4 py-3 border-b flex items-center justify-between">
        <div class="font-semibold text-sm">
            Kalender Jadwal — {{ $calendar['month_label'] ?? '' }}
        </div>
        <span class="text-xs text-gray-500">
            Tampilan bulanan
        </span>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-[1400px] w-full border-collapse text-[11px]">
            <thead>
                <tr>
                    <th class="sticky left-0 z-10 bg-gray-100 border px-3 py-2 w-40 text-left">
                        Lane
                    </th>
                    @foreach ($calendar['days'] as $day)
                        <th class="border px-1 py-2 text-center w-10
                            {{ $day['isWeekend'] ? 'bg-rose-50 text-rose-600' : 'bg-gray-50' }}">
                            <div class="text-[9px] uppercase">
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
                        <td class="sticky left-0 bg-white border px-3 py-2 font-medium">
                            {{ $laneLabel }}
                        </td>

                        @for ($d = 1; $d <= $calendar['days_count']; $d++)
                            <td class="border px-1 py-1 align-top">
                                @if (!empty($calendar['bucket'][$laneKey][$d]))
                                    @foreach ($calendar['bucket'][$laneKey][$d] as $chip)
                                        <div class="mb-1 rounded bg-slate-50 border px-1 py-0.5 text-[10px] truncate">
                                            <div class="font-semibold text-slate-700">
                                                {{ $chip['short'] }}
                                            </div>
                                            <div class="text-slate-500">
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

    <div class="px-4 py-2 text-[11px] text-gray-600 border-t">
        <span class="text-rose-600">Hari merah = weekend</span>
    </div>
</div>
