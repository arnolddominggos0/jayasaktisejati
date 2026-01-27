<x-filament-panels::page>
    <div class="space-y-8">

        {{-- HEADER --}}
        <div class="flex items-start justify-between pt-2">
            <div>
                <h1 class="text-2xl font-bold">Monitoring Jadwal Kapal TAM</h1>
                <p class="text-sm text-gray-500 mt-1">
                    Monitoring SLA dan evaluasi pelayaran kapal
                </p>
            </div>

            <div class="flex gap-2">
                <select wire:model.live="period"
                    class="w-56 rounded-xl border-gray-300 shadow-sm text-sm">
                    @foreach ($this->monthOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>

                <select wire:model.live="filter"
                    class="w-48 rounded-xl border-gray-300 shadow-sm text-sm">
                    <option value="all">Semua</option>
                    <option value="ongoing">Sedang Berjalan</option>
                    <option value="risk">Berisiko</option>
                    <option value="late">Terlambat</option>
                </select>
            </div>
        </div>

        {{-- KALENDER --}}
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
                                <th class="border px-1 py-2 text-center w-10
                                    {{ $day['isWeekend'] ? 'bg-rose-50 text-rose-600' : 'bg-gray-50' }}">
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
                                                <div
                                                    class="mb-1 rounded bg-slate-50 border px-1 py-0.5 text-[10px] truncate">
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

        {{-- MONITORING TABLE --}}
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
                            <th class="px-4 py-3 text-center">SLA Sailing</th>
                            <th class="px-4 py-3 text-center">Status SLA</th>
                            <th class="px-4 py-3 text-center">Terlambat</th>
                            <th class="px-4 py-3 text-left">Vessel Check</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($this->rows as $r)
                            @php($v = $r->voyage)
                            @php($sla = $v?->sailingSla)

                            <tr class="border-t align-top">
                                <td class="px-4 py-3 font-semibold text-primary-700">
                                    {{ $r->jss }}
                                </td>

                                <td class="px-4 py-3">{{ $v?->vessel?->name }}</td>
                                <td class="px-4 py-3">{{ $v?->voyage_no }}</td>
                                <td class="px-4 py-3">
                                    {{ $v?->pol?->code }} → {{ $v?->pod?->code }}
                                </td>

                                <td class="px-4 py-3 text-center">
                                    {{ optional($v?->etd)->format('d M Y') }}
                                </td>

                                {{-- SLA / IN PROGRESS --}}
                                <td class="px-4 py-3 text-center">
                                    @if ($sla)
                                        <div class="font-semibold">
                                            {{ number_format($sla->actual_days, 2) }} / {{ $sla->target_days }}
                                        </div>
                                        <div class="text-[10px] text-gray-500">hari</div>

                                    @elseif ($v?->sailing_elapsed_days && $v?->sailing_target_days)
                                        <div class="font-semibold">
                                            Day {{ number_format($v->sailing_elapsed_days, 1) }}
                                            / {{ $v->sailing_target_days }}
                                        </div>
                                        <div class="text-[10px]
                                            {{ match($v->sailing_progress_level) {
                                                'late' => 'text-red-600',
                                                'warning' => 'text-yellow-600',
                                                default => 'text-green-600',
                                            } }}">
                                            Sedang Berlayar
                                        </div>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>

                                {{-- STATUS --}}
                                <td class="px-4 py-3 text-center">
                                    @if ($sla)
                                        <span class="inline-flex px-2 py-1 rounded-full text-xs font-semibold
                                            {{ $sla->status === 'late'
                                                ? 'bg-red-100 text-red-700'
                                                : 'bg-green-100 text-green-700' }}">
                                            {{ strtoupper($sla->status) }}
                                        </span>

                                    @elseif ($v?->sailing_elapsed_days)
                                        <span class="inline-flex px-2 py-1 rounded-full text-xs font-semibold
                                            {{ match($v->sailing_progress_level) {
                                                'late' => 'bg-red-100 text-red-700',
                                                'warning' => 'bg-yellow-100 text-yellow-700',
                                                default => 'bg-green-100 text-green-700',
                                            } }}">
                                            ON SAILING
                                        </span>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>

                                {{-- TERLAMBAT --}}
                                <td class="px-4 py-3 text-center">
                                    @if ($sla && $sla->late_days > 0)
                                        <span class="text-red-600 font-semibold">
                                            {{ number_format($sla->late_days, 2) }}
                                        </span>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>

                                {{-- VESSEL CHECK --}}
                                <td class="px-4 py-3 text-xs">
                                    <div class="space-y-1 min-w-[180px]">
                                        @foreach ($r->vesselChecks as $check)
                                            @php($status = $check->status)
                                            <div class="flex items-center justify-between gap-2 px-2 py-1 rounded-md text-[11px]
                                                {{ $status->color() }}">
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
                                <td colspan="9" class="text-center py-8 text-gray-400">
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
    