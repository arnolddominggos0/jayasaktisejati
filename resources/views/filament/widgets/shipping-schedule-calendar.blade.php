@php($d = $this->getData())

<div class="space-y-5">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-semibold">Kalender Jadwal Kapal — {{ $d['month_label'] }}</h2>
            <p class="text-xs text-gray-500">
                Total Cargo Plan (ETD bulan ini):
                <span class="font-semibold text-gray-700">{{ $d['total_plan'] ?? 0 }}</span>
            </p>
        </div>
        <div class="flex gap-2">
            <a href="?month={{ $d['prev'] }}" class="fi-btn fi-btn-size-sm fi-btn-color-gray">Bulan Sebelumnya</a>
            <a href="?month={{ now()->format('Y-m') }}" class="fi-btn fi-btn-size-sm fi-btn-color-gray">Bulan Ini</a>
            <a href="?month={{ $d['next'] }}" class="fi-btn fi-btn-size-sm fi-btn-color-gray">Bulan Berikutnya</a>
        </div>
    </div>

    <div class="overflow-x-auto rounded-xl border bg-white shadow">
        <div class="max-h-[520px] overflow-y-auto">
            <table class="min-w-[1100px] w-full border-collapse text-[13px] leading-tight">
                <thead class="bg-gray-50 sticky top-0 z-10">
                    <tr class="border-b">
                        <th class="sticky left-0 bg-gray-50 px-3 py-2 text-left w-44">Lane</th>
                        @php($today = now()->toDateString())
                        @foreach (($d['days'] ?? []) as $day)
                        @php
                        $weekend = !empty($day['isWeekend']) ? 'bg-rose-50 text-rose-600' : 'text-gray-700';
                        $isToday = (($day['date'] ?? null) === $today) ? 'ring-1 ring-blue-400 rounded-sm' : '';
                        @endphp
                        <th class="px-1.5 py-2 text-center font-medium {{ $weekend }} {{ $isToday }}">
                            {{ $day['n'] ?? '' }}
                        </th>
                        @endforeach
                    </tr>
                </thead>

                <tbody>
                    @foreach (($d['lanes'] ?? []) as $key => $label)
                    <tr class="border-t">
                        <td class="sticky left-0 bg-white font-semibold px-3 py-2 text-gray-800">
                            {{ $label }}
                        </td>

                        @for ($i = 1; $i <= ($d['days_count'] ?? 0); $i++)
                            @php
                            $laneColor=match ($key) { 'plan_etd'=> 'bg-blue-50 border-blue-300 text-blue-800',
                            'plan_eta' => 'bg-amber-50 border-amber-300 text-amber-800',
                            'act_atd' => 'bg-emerald-50 border-emerald-300 text-emerald-800',
                            'act_ata' => 'bg-purple-50 border-purple-300 text-purple-800',
                            'sum_atd' => 'bg-gray-50 border-gray-200 text-gray-700',
                            default => 'bg-gray-50 border-gray-200 text-gray-700',
                            };
                            $chips = $d['bucket'][$key][$i] ?? [];
                            @endphp

                            <td class="align-top px-1 py-1">
                                <div class="space-y-1 min-h-[84px]">
                                    @foreach ($chips as $chip)
                                    @if ($key === 'sum_atd')
                                    <div class="inline-flex items-center justify-center w-10 h-6 rounded-md border {{ $laneColor }} text-[11px]">
                                        {{ $chip['label'] ?? '0' }}
                                    </div>
                                    @else
                                    <div class="rounded-md border {{ $laneColor }} px-2 py-1 hover:shadow-sm transition">
                                        <div class="font-semibold text-[11px]">{{ $chip['label'] ?? '' }}</div>
                                        @if (!empty($chip['head']))
                                        <div class="text-[11px]">{{ $chip['head'] }}</div>
                                        @endif
                                        @if (!empty($chip['sub']))
                                        <div class="text-[10px] text-gray-600">{{ $chip['sub'] }}</div>
                                        @endif
                                    </div>
                                    @endif
                                    @endforeach
                                </div>
                            </td>
                            @endfor
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>