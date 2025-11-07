@php($d = $this->getData())

<div class="space-y-5">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-semibold">Kalender Jadwal Kapal — {{ $d['month_label'] ?? '' }}</h2>
            <p class="text-xs text-gray-500">
                Total Cargo Plan (ETD bulan ini):
                <span class="font-semibold text-gray-700">{{ $d['total_plan'] ?? 0 }}</span>
            </p>
        </div>

        <div class="flex items-center gap-2 text-sm">
            <label>Bulan:</label>
            <select wire:model.live="monthNum" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                @foreach ($d['month_options'] as $num => $label)
                <option value="{{ $num }}">{{ $label }}</option>
                @endforeach
            </select>

            <label>Tahun:</label>
            <select wire:model.live="year" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                @foreach ($d['year_options'] as $yy)
                <option value="{{ $yy }}">{{ $yy }}</option>
                @endforeach
            </select>

            <button
                wire:click="$set('year', {{ now()->year }}); $set('monthNum', {{ now()->month }})"
                class="text-blue-600 underline hover:text-blue-800 transition">
                Bulan Ini
            </button>
        </div>

    </div>

    <div class="overflow-x-auto rounded-xl border bg-white shadow">
        <div class="max-h-[540px] overflow-y-auto">
            <table class="min-w-[1200px] w-full border-collapse text-[12px] leading-tight">
                <thead class="sticky top-0 z-10">
                    <tr>
                        <th class="sticky left-0 bg-gray-50 border border-gray-200 w-48 px-3 py-2 text-left text-[12px] font-semibold">
                            Lane
                        </th>
                        @foreach (($d['days'] ?? []) as $day)
                        @php($isWeekend = !empty($day['isWeekend']))
                        @php($isToday = (($day['date'] ?? null) === ($d['today'] ?? '')))
                        <th class="border border-gray-200 text-center align-middle w-9 min-w-9 py-1
                                       {{ $isWeekend ? 'bg-rose-50 text-rose-700' : 'bg-gray-50 text-gray-800' }}
                                       {{ $isToday ? 'ring-1 ring-blue-400' : '' }}"
                            title="{{ $day['date'] ?? '' }}">
                            <div class="text-[10px] leading-3">{{ $day['dow'] ?? '' }}</div>
                            <div class="text-[12px] font-semibold">{{ $day['n'] ?? '' }}</div>
                        </th>
                        @endforeach
                    </tr>
                </thead>

                <tbody>
                    @foreach (($d['lanes'] ?? []) as $key => $label)
                    <tr>
                        <td class="sticky left-0 bg-white border border-gray-200 px-3 py-2 font-semibold text-gray-800">
                            {{ $label }}
                        </td>

                        @for ($i = 1; $i <= ($d['days_count'] ?? 0); $i++)
                            @php($laneColor=match ($key) { 'plan_etd'=> 'bg-yellow-50 text-yellow-900',
                            'plan_eta' => 'bg-amber-50 text-amber-900',
                            'act_atd' => 'bg-emerald-50 text-emerald-900',
                            'act_ata' => 'bg-purple-50 text-purple-900',
                            'sum_atd' => 'bg-orange-50 text-orange-900',
                            default => 'bg-gray-50 text-gray-900',
                            })
                            @php($chips = $d['bucket'][$key][$i] ?? [])
                            <td class="border border-gray-200 align-top p-0">
                                <div class="min-h-8 max-h-[68px] overflow-y-auto px-1 py-0.5 space-y-0.5">
                                    @foreach ($chips as $chip)
                                    @if ($key === 'sum_atd')
                                    <div class="mx-auto my-0.5 h-6 w-8 text-center text-[11px] font-semibold {{ $laneColor }} rounded-sm border border-gray-200 flex items-center justify-center">
                                        {{ $chip['short'] ?? $chip['label'] ?? '0' }}
                                    </div>
                                    @else
                                    <div class="h-6 px-1.5 text-[11px] leading-6 truncate {{ $laneColor }} rounded-sm border border-gray-200"
                                        title="{{ ($chip['label'] ?? '') . ' | ' .    ($chip['head'] ?? '') . ' | ' . ($chip['sub'] ?? '') }}">
                                        {{ $chip['short'] ?? $chip['label'] ?? '' }}
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

    <div class="text-[11px] text-gray-600">
        <div class="flex items-center gap-3">
            <span class="inline-block h-3 w-3 rounded-sm bg-yellow-200 border border-yellow-300"></span> ETD/ETA Plan
            <span class="inline-block h-3 w-3 rounded-sm bg-emerald-200 border border-emerald-300"></span> ATD Actual
            <span class="inline-block h-3 w-3 rounded-sm bg-purple-200 border border-purple-300"></span> ATA Actual
            <span class="inline-block h-3 w-3 rounded-sm bg-orange-200 border border-orange-300"></span> Vol. ATD (sum)
            <span class="ml-4 text-rose-700">Hari merah = weekend</span>
        </div>
    </div>
</div>