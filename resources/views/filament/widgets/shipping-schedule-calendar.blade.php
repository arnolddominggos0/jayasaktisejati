@php($d = $this->getData())

<div class="space-y-4">
    <div class="flex items-center justify-between">
        <div>
            <div class="text-xl font-semibold">Kalender Jadwal Kapal — {{ $d['month_label'] }}</div>
            <div class="text-sm text-gray-600">
                Total Cargo Plan (ETD bulan ini):
                <span class="font-semibold">{{ $d['total_plan'] ?? 0 }}</span>
            </div>
        </div>
        <div class="flex gap-2">
            <a href="?month={{ $d['prev'] }}" class="fi-btn fi-btn-size-sm fi-btn-color-gray">Bulan Sebelumnya</a>
            <a href="?month={{ now()->format('Y-m') }}" class="fi-btn fi-btn-size-sm fi-btn-color-gray">Bulan Ini</a>
            <a href="?month={{ $d['next'] }}" class="fi-btn fi-btn-size-sm fi-btn-color-gray">Bulan Berikutnya</a>
        </div>
    </div>

    <div class="overflow-x-auto rounded-lg border shadow-sm bg-white">
        <table class="min-w-[1200px] w-full text-sm border-collapse">
            <thead>
                <tr class="bg-gray-50 border-b">
                    <th class="sticky left-0 bg-gray-50 px-3 py-2 text-left w-48">Lane</th>
                    @foreach ($d['days'] as $day)
                        <th class="px-2 py-2 text-center font-medium {{ $day['isWeekend'] ? 'text-rose-600' : '' }}">
                            {{ $day['n'] }}
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($d['lanes'] as $key => $label)
                    <tr class="border-t">
                        <td class="sticky left-0 bg-white font-medium px-3 py-2">{{ $label }}</td>
                        @for ($i = 1; $i <= $d['days_count']; $i++)
                            <td class="align-top px-1 py-1">
                                @foreach ($d['bucket'][$key][$i] as $chip)
                                    <div class="rounded-md bg-blue-50 border px-2 py-1 mb-1">
                                        <div class="font-semibold text-blue-800 text-xs">{{ $chip['label'] }}</div>
                                        @if ($chip['head'])
                                            <div class="text-xs">{{ $chip['head'] }}</div>
                                        @endif
                                        @if ($chip['sub'])
                                            <div class="text-[10px] text-gray-600">{{ $chip['sub'] }}</div>
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
</div>
