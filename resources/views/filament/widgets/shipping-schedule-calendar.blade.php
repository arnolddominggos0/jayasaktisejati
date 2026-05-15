<div class="bg-white dark:bg-slate-900 rounded-xl border dark:border-slate-800 overflow-hidden">
    <div class="px-4 py-3 border-b dark:border-slate-800 flex items-center justify-between">
        <div class="font-semibold text-sm dark:text-white">
            Kalender Jadwal — {{ $calendar['month_label'] ?? '' }}
        </div>
        <span class="text-xs text-gray-500 dark:text-slate-400">
            Tampilan bulanan
        </span>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-[1400px] w-full border-collapse text-[11px] dark:text-slate-300">
            <thead>
                <tr>
                    <th
                        class="sticky left-0 z-10 bg-gray-100 dark:bg-slate-800 border dark:border-slate-800 px-3 py-2 w-40 text-left">
                        Lane
                    </th>
                    @foreach ($calendar['days'] as $day)
                        <th
                            class="border dark:border-slate-800 px-1 py-2 text-center w-10
 {{ $day['isWeekend'] ? 'bg-rose-50 dark:bg-rose-950/30 text-rose-600 dark:text-rose-400' : 'bg-gray-50 dark:bg-slate-800' }}">
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
                        <td
                            class="sticky left-0 bg-white dark:bg-slate-900 border dark:border-slate-800 px-3 py-2 font-medium">
                            {{ $laneLabel }}
                        </td>

                        @for ($d = 1; $d <= $calendar['days_count']; $d++)
                            <td class="border dark:border-slate-800 px-1 py-1 align-top">
                                @if (!empty($calendar['bucket'][$laneKey][$d]))
                                    @foreach ($calendar['bucket'][$laneKey][$d] as $chip)
                                        <div
                                            class="mb-1 rounded bg-slate-50 dark:bg-slate-800 border dark:border-slate-800 px-1 py-0.5 text-[10px] truncate">
                                            <div class="font-semibold text-slate-700 dark:text-slate-300">
                                                {{ $chip['short'] }}
                                            </div>
                                            <div class="text-slate-500 dark:text-slate-400">
                                                {{ $chip['voyage_no'] }}
                                            </div>
                                        </div>
                                    @endforeach
                                @else
                                    <span class="text-gray-300 dark:text-slate-500"> —</span>
                                @endif
                            </td>
                        @endfor
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="px-4 py-2 text-[11px] text-gray-600 dark:text-slate-400 border-t dark:border-slate-800">
        <span class="text-rose-600 dark:text-rose-400">Hari merah = weekend</span>
    </div>
</div>
