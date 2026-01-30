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
                    <th class="px-4 py-3 text-center">SLA Pelayaran</th>
                    <th class="px-4 py-3 text-center">Status SLA</th>
                    <th class="px-4 py-3 text-center">Keterlambatan</th>
                    <th class="px-4 py-3 text-left">Alasan Keterlambatan</th>
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
                            {{ optional($v?->etd)->format('d M Y') ?? '—' }}
                        </td>

                        {{-- SLA --}}
                        <td class="px-4 py-3 text-center">
                            @if ($sla)
                                <div class="font-semibold">
                                    {{ number_format($sla->actual_days, 2) }}
                                    / {{ $sla->target_days }}
                                </div>
                                <div class="text-[10px] text-gray-500">hari</div>
                            @else
                                —
                            @endif
                        </td>

                        {{-- Status SLA --}}
                        <td class="text-center">
                            @if ($sla)
                                <span
                                    class="px-2 py-1 rounded-full text-xs font-semibold
                                    {{ $sla->status === 'late' ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700' }}">
                                    {{ $sla->status === 'late' ? 'SLA Terlambat' : 'SLA Tercapai' }}
                                </span>
                            @else
                                —
                            @endif
                        </td>

                        {{-- Keterlambatan --}}
                        <td class="text-center">
                            @if ($v->is_delayed)
                                <span
                                    class="px-2 py-1 rounded-full bg-yellow-100 text-yellow-700 text-xs font-semibold">
                                    Jadwal Direvisi
                                </span>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>

                        <td>
                            {{ $v->is_delayed ? $v->delay_reason?->label() : '—' }}
                        </td>


                        {{-- Alasan Keterlambatan --}}
                        <td class="px-4 py-3 text-sm">
                            @if ($v->is_delayed)
                                @if ($v->delay_reason)
                                    <span class="text-orange-700 font-medium">
                                        {{ $v->delay_reason->label() }}
                                    </span>
                                @else
                                    <span class="text-gray-400 italic">
                                        Alasan belum diisi
                                    </span>
                                @endif
                            @else
                                —
                            @endif
                        </td>

                        {{-- Vessel Check --}}
                        <td class="px-4 py-3 text-xs">
                            <div class="space-y-1 min-w-[180px]">
                                @foreach ($r->vesselChecks as $check)
                                    @php($status = $check->status)
                                    <div
                                        class="flex items-center justify-between gap-2 px-2 py-1 rounded-md text-[11px]
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
                        <td colspan="10" class="text-center py-8 text-gray-400">
                            Tidak ada data
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
