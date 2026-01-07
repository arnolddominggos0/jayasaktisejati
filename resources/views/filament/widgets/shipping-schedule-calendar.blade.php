<x-filament::section>
    <div class="flex items-center justify-between mb-3">
        <div class="font-semibold text-sm">
            Kalender Jadwal — {{ $calendar['month_label'] ?? '' }}
        </div>
        <span class="text-xs text-gray-500">Tampilan bulanan</span>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-[1500px] w-full border-collapse text-[11px] table-fixed">
            <thead>
                <tr>
                    <th class="sticky left-0 z-20 bg-gray-100 border px-3 py-2 w-48 text-left">
                        Lane / Event
                    </th>

                    @foreach ($calendar['days'] as $day)
                        <th class="border px-1 py-2 text-center w-14
                            {{ $day['isWeekend'] ? 'bg-rose-50 text-rose-600' : 'bg-gray-50 text-gray-700' }}">
                            <div class="text-[9px] uppercase tracking-wide">
                                {{ $day['dow'] }}
                            </div>
                            <div class="font-semibold text-xs">
                                {{ $day['n'] }}
                            </div>
                        </th>
                    @endforeach
                </tr>
            </thead>

            <tbody>
                @foreach ($calendar['lanes'] as $laneKey => $laneLabel)
                    @foreach (['etd','eta','atd','ata'] as $event)
                        <tr>
                            <td class="sticky left-0 z-10 bg-white border px-3 py-2">
                                <div class="font-medium">{{ $laneLabel }}</div>
                                <div class="text-[10px] uppercase text-gray-400">
                                    {{ strtoupper($event) }}
                                </div>
                            </td>

                            @for ($d = 1; $d <= $calendar['days_count']; $d++)
                                <td class="border px-1 py-1 align-top">
                                    @forelse ($calendar['bucket'][$laneKey][$event][$d] ?? [] as $chip)
                                        <div class="
                                            mb-1 rounded-md px-2 py-1 border
                                            {{ str_starts_with($event, 'a')
                                                ? 'bg-emerald-50 border-emerald-200 text-emerald-700'
                                                : 'bg-primary-50 border-primary-200 text-primary-700' }}">
                                            <div class="text-[10px] font-semibold leading-tight">
                                                {{ $chip['vessel'] }}
                                            </div>
                                            <div class="text-[9px] leading-tight">
                                                {{ $chip['voyage_no'] }}
                                            </div>
                                            <div class="text-[9px] text-gray-600 leading-tight">
                                                {{ $chip['pol'] }} → {{ $chip['pod'] }}
                                            </div>
                                        </div>
                                    @empty
                                        <span class="text-gray-300 text-xs">—</span>
                                    @endforelse
                                </td>
                            @endfor
                        </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>
    </div>
</x-filament::section>
