<x-filament-panels::page>
    <div class="space-y-8">

        <div class="flex items-start justify-between pt-2">
            <div>
                <h1 class="text-2xl font-bold">Monitoring Jadwal Kapal TAM</h1>
                <p class="text-sm text-gray-500 mt-1">
                    Monitoring SLA dan evaluasi pelayaran kapal
                </p>
            </div>

            <div class="flex gap-2">
                <select wire:model.live="period" class="w-56 rounded-xl border-gray-300 shadow-sm text-sm">
                    @foreach ($this->monthOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>

                <select wire:model.live="filter" class="w-48 rounded-xl border-gray-300 shadow-sm text-sm">
                    <option value="all">Semua</option>
                    <option value="ongoing">Sedang Berjalan</option>
                    <option value="risk">Berisiko</option>
                    <option value="late">Late</option>
                </select>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border overflow-hidden">
            <div class="px-4 py-3 border-b font-semibold text-sm">
                Kalender Jadwal — {{ $this->calendar['month_label'] ?? '' }}
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-[1400px] w-full border-collapse text-[11px]">
                    <thead>
                        <tr>
                            <th class="sticky left-0 bg-gray-100 border px-3 py-2 w-40 text-left">
                                Lane
                            </th>
                            @foreach ($this->calendar['days'] as $day)
                                <th class="border px-1 py-2 text-center w-10 {{ $day['isWeekend'] ? 'bg-rose-50 text-rose-600' : 'bg-gray-50' }}">
                                    <div class="text-[9px] uppercase">{{ $day['dow'] }}</div>
                                    <div class="font-semibold">{{ $day['n'] }}</div>
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

        <div class="bg-white rounded-2xl shadow-sm border overflow-hidden">
            <div class="px-4 py-3 border-b flex justify-between">
                <div class="font-semibold text-sm">Monitoring Kapal</div>
                <span class="text-xs text-gray-500">{{ count($this->rows) }} voyage</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm border-collapse">
                    <thead class="bg-gray-50">
                        <tr class="text-xs uppercase text-gray-600">
                            <th class="px-4 py-3 text-left">JSS</th>
                            <th class="px-4 py-3 text-left">Kapal</th>
                            <th class="px-4 py-3 text-left">Voyage</th>
                            <th class="px-4 py-3 text-left">Rute</th>
                            <th class="px-4 py-3 text-center">ETD</th>
                            <th class="px-4 py-3 text-left">Vessel Check</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($this->rows as $r)
                            @php($v = $r->voyage)
                            <tr class="border-t align-top">
                                <td class="px-4 py-3 font-semibold text-primary-700">{{ $r->jss }}</td>
                                <td class="px-4 py-3">{{ $v?->vessel?->name }}</td>
                                <td class="px-4 py-3">{{ $v?->voyage_no }}</td>
                                <td class="px-4 py-3">{{ $v?->pol?->code }} → {{ $v?->pod?->code }}</td>
                                <td class="px-4 py-3 text-center">{{ optional($v?->etd)->format('d M Y') }}</td>
                                <td class="px-4 py-3 text-xs">
                                    <div class="space-y-1 min-w-[180px]">
                                        @foreach ($r->vesselChecks as $check)
                                            @php($status = $check->status)
                                            <div class="flex items-center justify-between gap-2 px-2 py-1 rounded-md text-[11px] {{ $status->color() }}">
                                                <span class="font-semibold">{{ $check->day_code }}</span>
                                                <span>{{ $check->check_date->format('d M') }}</span>
                                                <span class="font-semibold">{{ $status->label() }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-8 text-gray-400">
                                    Tidak ada data
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</x-filament-panels::page>
