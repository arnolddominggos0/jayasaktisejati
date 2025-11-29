@php($d = $this->getData())

<div class="space-y-5">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-semibold">
                Kalender Jadwal Kapal — {{ $d['month_label'] ?? '' }}
            </h2>

            <p class="text-xs text-gray-500">
                Total Cargo Plan (ETD bulan ini):
                <span class="font-semibold text-gray-700">{{ $d['total_plan'] ?? 0 }}</span>
            </p>

            @if (!empty($d['kpi']))
                <div class="mt-1 flex flex-wrap gap-3 text-[11px] text-gray-600">
                    <div>
                        Voyage selesai:
                        <span class="font-semibold">{{ $d['kpi']['total'] }}</span>
                    </div>
                    <div>
                        On-time:
                        <span class="font-semibold text-emerald-700">{{ $d['kpi']['on_time'] }}</span>
                    </div>
                    <div>
                        Terlambat:
                        <span class="font-semibold text-rose-700">{{ $d['kpi']['late'] }}</span>
                    </div>
                    <div>
                        Pencapaian KPI:
                        <span class="font-semibold">{{ $d['kpi']['completion'] }}%</span>
                    </div>
                </div>
            @endif
        </div>

        <div class="flex items-center gap-2">
            <select wire:model.live="monthNum" class="fi-input w-40 h-9 border rounded px-2 text-sm">
                @foreach ($d['month_options'] as $num => $label)
                    <option value="{{ $num }}">{{ $label }}</option>
                @endforeach
            </select>

            <select wire:model.live="year" class="fi-input w-28 h-9 border rounded px-2 text-sm">
                @foreach ($d['year_options'] as $yy)
                    <option value="{{ $yy }}">{{ $yy }}</option>
                @endforeach
            </select>

            <button
                class="fi-btn fi-btn-size-sm fi-btn-color-gray"
                wire:click="$set('year', {{ now()->year }}); $set('monthNum', {{ now()->month }})"
            >
                Bulan Ini
            </button>
        </div>
    </div>

    <div class="overflow-x-auto rounded-xl border bg-white shadow">
        <div class="max-h-[540px] overflow-y-auto">
            <table class="min-w-[1200px] w-full border-collapse text-[12px] leading-tight">
                <thead class="sticky top-0 z-10">
                    <tr>
                        <th
                            class="sticky left-0 bg-gray-50 border border-gray-200 w-48 px-3 py-2 text-left text-[12px] font-semibold">
                            Lane
                        </th>
                        @foreach (($d['days'] ?? []) as $day)
                            @php($isWeekend = !empty($day['isWeekend']))
                            @php($isToday = (($day['date'] ?? null) === ($d['today'] ?? '')))
                            <th
                                class="border border-gray-200 text-center align-middle w-9 min-w-9 py-1
                                       {{ $isWeekend ? 'bg-rose-50 text-rose-700' : 'bg-gray-50 text-gray-800' }}
                                       {{ $isToday ? 'ring-1 ring-blue-400' : '' }}"
                                title="{{ $day['date'] ?? '' }}"
                            >
                                <div class="text-[10px] leading-3">{{ $day['dow'] ?? '' }}</div>
                                <div class="text-[12px] font-semibold">{{ $day['n'] ?? '' }}</div>
                            </th>
                        @endforeach
                    </tr>
                </thead>

                <tbody>
                    @foreach (($d['lanes'] ?? []) as $key => $label)
                        <tr>
                            <td
                                class="sticky left-0 bg-white border border-gray-200 px-3 py-2 font-semibold text-gray-800">
                                {{ $label }}
                            </td>

                            @for ($i = 1; $i <= ($d['days_count'] ?? 0); $i++)
                                @php($chips = $d['bucket'][$key][$i] ?? [])

                                <td class="border border-gray-200 align-top p-0">
                                    <div class="min-h-8 max-h-[68px] overflow-y-auto px-1 py-0.5 space-y-0.5">
                                        @foreach ($chips as $chip)
                                            @if ($key === 'sum_atd')
                                                <div class="{{ $chip['class'] ?? '' }}">
                                                    {{ $chip['short'] ?? $chip['label'] ?? '0' }}
                                                </div>
                                            @else
                                                <div
                                                    class="h-6 px-1.5 text-[11px] leading-6 truncate rounded-sm {{ $chip['class'] ?? '' }}"
                                                    title="{{ ($chip['label'] ?? '') . ' | ' . ($chip['head'] ?? '') . ' | ' . ($chip['sub'] ?? '') }}"
                                                >
                                                    {{ $chip['short'] ?? $chip['label'] ?? '' }}

                                                    @if (!empty($chip['is_urgent']))
                                                        <span class="ml-0.5 text-[9px] font-semibold">U</span>
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

    <div class="text-[11px] text-gray-600 space-y-1">
        <div class="flex flex-wrap items-center gap-3">
            <span class="inline-block h-3 w-3 rounded-sm bg-yellow-200 border border-yellow-300"></span> ETD/ETA Plan
            <span class="inline-block h-3 w-3 rounded-sm bg-emerald-200 border border-emerald-300"></span> ATD Actual
            <span class="inline-block h-3 w-3 rounded-sm bg-purple-200 border border-purple-300"></span> ATA Actual
            <span class="inline-block h-3 w-3 rounded-sm bg-orange-200 border border-orange-300"></span> Vol. ATD (sum)
            <span class="ml-4 text-rose-700">Hari merah = weekend</span>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <span class="inline-block h-3 w-3 rounded-sm bg-emerald-100 border border-emerald-300"></span>
            <span>Actual on-time (≤ 19 hari / 17 hari urgent)</span>

            <span class="inline-block h-3 w-3 rounded-sm bg-rose-100 border border-rose-300"></span>
            <span>Actual terlambat (lewat SLA TAM)</span>

            <span class="inline-block h-3 w-3 rounded-sm border border-gray-400 text-[9px] flex items-center justify-center"
                  style="width: 14px; height: 14px;">
                U
            </span>
            <span>Voyage urgent</span>
        </div>
    </div>

    @if (!empty($d['voyage_table']))
        <div class="mt-6 bg-white border rounded-xl p-4 shadow-sm">
            <h3 class="font-semibold mb-3 text-sm">Rangkuman Voyage & SLA</h3>

            <table class="w-full text-[11px] border-collapse">
                <thead>
                    <tr class="bg-gray-50 border-b">
                        <th class="p-2 border text-left">Voyage</th>
                        <th class="p-2 border text-left">Vessel</th>
                        <th class="p-2 border text-left">Line</th>
                        <th class="p-2 border text-center">ETD</th>
                        <th class="p-2 border text-center">ETA</th>
                        <th class="p-2 border text-center">ATD</th>
                        <th class="p-2 border text-center">ATA</th>
                        <th class="p-2 border text-center">Lead Time</th>
                        <th class="p-2 border text-center">SLA</th>
                        <th class="p-2 border text-center">Vol</th>
                        <th class="p-2 border text-center">Urgent</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($d['voyage_table'] as $row)
                        <tr class="border-b hover:bg-gray-50">
                            <td class="p-2 border">{{ $row['voyage'] }}</td>
                            <td class="p-2 border">{{ $row['vessel'] }}</td>
                            <td class="p-2 border">{{ $row['line'] }}</td>
                            <td class="p-2 border text-center">{{ $row['etd'] }}</td>
                            <td class="p-2 border text-center">{{ $row['eta'] }}</td>
                            <td class="p-2 border text-center">{{ $row['atd'] }}</td>
                            <td class="p-2 border text-center">{{ $row['ata'] }}</td>
                            <td class="p-2 border text-center">{{ $row['lead'] }}</td>

                            <td class="p-2 border text-center
                                @if($row['sla'] === 'late') text-red-600 font-semibold
                                @elseif($row['sla'] === 'on_time') text-green-600 font-semibold
                                @endif">
                                {{ $row['sla'] === 'on_time' ? 'On-time' : ($row['sla'] === 'late' ? 'Late' : '-') }}
                            </td>

                            <td class="p-2 border text-center">{{ $row['volume'] }}</td>

                            <td class="p-2 border text-center">
                                @if($row['urgent'])
                                    <span class="text-red-600 font-bold text-xs">U</span>
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
