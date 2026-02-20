<div class="bg-white rounded-2xl shadow-sm border overflow-hidden">
    <div class="px-4 py-3 border-b flex justify-between">
        <div class="font-semibold text-sm">Monitoring Pelayaran</div>
        <span class="text-xs text-gray-500">{{ count($this->rows) }} pelayaran</span>
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
                    <th class="px-4 py-3 text-center">ATD</th>
                    <th class="px-4 py-3 text-center">OTD</th>

                    <th class="px-4 py-3 text-center">ETA</th>
                    <th class="px-4 py-3 text-center">ATA</th>
                    <th class="px-4 py-3 text-center">OTA</th>

                    <th class="px-4 py-3 text-center">Transit SLA</th>

                    <th class="px-4 py-3 text-left">Pemeriksaan Kapal</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($this->rows as $r)
                    @php($v = $r->voyage)
                    @php($sla = $v?->sailingSla)

                    <tr class="border-t align-top">

                        {{-- JSS --}}
                        <td class="px-4 py-3 font-semibold text-primary-700">
                            {{ $r->jss }}
                        </td>

                        {{-- Kapal --}}
                        <td class="px-4 py-3">
                            {{ $v?->vessel?->name ?? '—' }}
                        </td>

                        {{-- Voyage --}}
                        <td class="px-4 py-3">
                            {{ $v?->voyage_no ?? '—' }}
                        </td>

                        {{-- Rute --}}
                        <td class="px-4 py-3">
                            {{ $v?->pol?->code }} → {{ $v?->pod?->code }}
                        </td>

                        {{-- ETD --}}
                        <td class="px-4 py-3 text-center">
                            {{ optional($v?->etd)->format('d M Y H:i') ?? '—' }}
                        </td>

                        {{-- ATD --}}
                        <td class="px-4 py-3 text-center">
                            {{ optional($v?->atd_at)->format('d M Y H:i') ?? '—' }}
                        </td>

                        {{-- OTD --}}
                        <td class="px-4 py-3 text-center">
                            @if ($v?->otd_status === 'ontime')
                                <span class="px-2 py-1 bg-green-100 text-green-700 rounded text-xs font-semibold">
                                    On Time
                                </span>
                            @elseif ($v?->otd_status === 'late')
                                <span class="px-2 py-1 bg-red-100 text-red-700 rounded text-xs font-semibold">
                                    Late
                                </span>
                            @else
                                —
                            @endif
                        </td>

                        {{-- ETA --}}
                        <td class="px-4 py-3 text-center">
                            {{ optional($v?->eta)->format('d M Y H:i') ?? '—' }}
                        </td>

                        {{-- ATA --}}
                        <td class="px-4 py-3 text-center">
                            {{ optional($v?->ata_at)->format('d M Y H:i') ?? '—' }}
                        </td>

                        {{-- OTA --}}
                        <td class="px-4 py-3 text-center">
                            @if ($v?->ota_status === 'ontime')
                                <span class="px-2 py-1 bg-green-100 text-green-700 rounded text-xs font-semibold">
                                    On Time
                                </span>
                            @elseif ($v?->ota_status === 'late')
                                <span class="px-2 py-1 bg-red-100 text-red-700 rounded text-xs font-semibold">
                                    Late
                                </span>
                            @else
                                —
                            @endif
                        </td>

                        {{-- Transit SLA --}}
                        <td class="px-4 py-3 text-center">
                            @if ($sla)
                                <span class="px-2 py-1 rounded-full text-xs font-semibold {{ $sla->status->color() }}">
                                    {{ $sla->status->label() }}
                                </span>
                                <div class="text-[10px] text-gray-500 mt-1">
                                    {{ number_format($sla->actual_days, 2) }} /
                                    {{ $sla->target_days }} hari
                                </div>
                            @else
                                —
                            @endif
                        </td>

                        {{-- Pemeriksaan Kapal --}}
                        <td class="px-4 py-3 text-xs">
                            <div class="space-y-1 min-w-[180px]">
                                @foreach ($r->vesselChecks as $check)
                                    @php($status = $check->status)
                                    <div
                                        class="flex items-center justify-between gap-2 px-2 py-1 rounded-md text-[11px] {{ $status->color() }}">
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
                        <td colspan="12" class="text-center py-8 text-gray-400">
                            Tidak ada data
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>