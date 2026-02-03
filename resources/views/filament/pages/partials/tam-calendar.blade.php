<div class="bg-white rounded-2xl border overflow-hidden">
    <div class="px-4 py-3 border-b font-semibold text-sm">
        Kalender Jadwal Pelayaran — {{ $calendar['month_label'] }}
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-[1400px] w-full border-collapse text-[11px]">
            <thead>
                <tr>
                    <th class="sticky left-0 bg-gray-100 border px-3 py-2 w-40 text-left">
                        Rute
                    </th>
                    @foreach ($this->calendar['days'] as $day)
                        <th
                            class="border px-1 py-2 text-center w-10
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
                @foreach ($this->calendar['lanes'] as $laneKey => $laneLabel)
                    <tr>
                        <td class="sticky left-0 bg-white border px-3 py-2 font-medium">
                            {{ $laneLabel }}
                        </td>
                        @for ($d = 1; $d <= $this->calendar['days_count']; $d++)
                            <td class="border px-1 py-1 align-top">
                                @if (!empty($this->calendar['bucket'][$laneKey][$d]))
                                    @foreach ($this->calendar['bucket'][$laneKey][$d] as $chip)
                                        <div
                                            class="mb-1 rounded-md bg-slate-50 border px-1 py-0.5 text-[10px] truncate">
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
    <div class="px-4 py-2 text-xs text-gray-600 border-t">
        <span class="text-rose-600 font-medium">tanggal merah = weekend</span>
    </div>
</div>
