<x-filament-panels::page>

<div class="space-y-8">

    <div class="flex items-start justify-between pt-2">
        <div>
            <h1 class="text-2xl font-bold leading-tight">
                Monitoring Jadwal Kapal TAM
            </h1>
            <p class="text-sm text-gray-500 mt-1">
                Monitoring SLA dan evaluasi pelayaran kapal
            </p>
        </div>

        <div class="flex gap-2">
            <div class="w-56">
                <select
                    wire:model.live="period"
                    class="w-full rounded-xl border-gray-300 shadow-sm text-sm">
                    @foreach ($monthOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="w-48">
                <select
                    wire:model.live="filter"
                    class="w-full rounded-xl border-gray-300 shadow-sm text-sm">
                    <option value="all">Semua</option>
                    <option value="ongoing">Sedang Berjalan</option>
                    <option value="risk">Berisiko</option>
                    <option value="late">Late</option>
                </select>
            </div>
        </div>
    </div>

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
            <table class="min-w-[1500px] w-full border-collapse text-[11px] table-fixed">
                <thead>
                    <tr>
                        <th class="sticky left-0 z-10 bg-gray-100 border px-3 py-2 w-48 text-left">
                            Lane
                        </th>
                        @foreach ($calendar['days'] as $day)
                            <th class="border px-1 py-2 text-center w-14
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
                                    @forelse ($calendar['bucket'][$laneKey][$d] ?? [] as $chip)
                                        <div class="mb-1 rounded-md bg-primary-50 px-2 py-1">
                                            <div class="text-[10px] font-semibold text-primary-700">
                                                {{ $chip['short'] }}
                                            </div>
                                            <div class="text-[9px] text-primary-600">
                                                {{ $chip['voyage_no'] }}
                                            </div>
                                        </div>
                                    @empty
                                        <span class="text-gray-300">—</span>
                                    @endforelse
                                </td>
                            @endfor
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border overflow-hidden">
        <div class="px-4 py-3 border-b flex items-center justify-between">
            <div class="font-semibold text-sm">
                Monitoring Kapal
            </div>
            <span class="text-xs text-gray-500">
                {{ count($rows) }} voyage
            </span>
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
                        <th class="px-4 py-3 text-center">ATA</th>
                        <th class="px-4 py-3 text-center">Hari Berjalan</th>
                        <th class="px-4 py-3 text-center">SLA</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($rows as $r)
                        @php $v = $r->voyage; @endphp
                        <tr class="border-t hover:bg-gray-50 transition">
                            <td class="px-4 py-3 font-semibold text-primary-700">
                                {{ $r->jss }}
                            </td>

                            <td class="px-4 py-3">
                                {{ $v?->vessel?->name ?? '—' }}
                            </td>

                            <td class="px-4 py-3">
                                {{ $v?->voyage_no ?? '—' }}
                            </td>

                            <td class="px-4 py-3 text-gray-600">
                                {{ $v?->pol?->code }} → {{ $v?->pod?->code }}
                            </td>

                            <td class="px-4 py-3 text-center">
                                {{ optional($v?->etd)->format('d M Y') ?? '—' }}
                            </td>

                            <td class="px-4 py-3 text-center">
                                {{ optional($v?->ata_at)->format('d M Y') ?? '—' }}
                            </td>

                            <td class="px-4 py-3 text-center font-semibold">
                                @if ($v?->elapsed_sailing_days !== null)
                                    <span class="
                                        {{ $v->risk_level === 'risk'
                                            ? 'text-red-600'
                                            : ($v->risk_level === 'warning'
                                                ? 'text-yellow-600'
                                                : 'text-emerald-600') }}">
                                        {{ $v->elapsed_sailing_days }} hari
                                    </span>
                                @else
                                    —
                                @endif
                            </td>

                            <td class="px-4 py-3 text-center font-semibold">
                                @if ($v?->sla_days !== null)
                                    <span class="{{ $v->sla_status === 'late' ? 'text-red-600' : 'text-emerald-600' }}">
                                        {{ $v->sla_days }} hari
                                    </span>
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-8 text-gray-400">
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
