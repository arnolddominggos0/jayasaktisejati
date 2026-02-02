<div class="bg-white rounded-2xl border overflow-hidden">
    <div class="px-4 py-3 border-b flex justify-between">
        <div class="font-semibold text-sm">Monitoring Pelayaran</div>
        <span class="text-xs text-gray-500">{{ count($rows) }} pelayaran</span>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-xs uppercase text-gray-600">
                <tr>
                    <th class="px-4 py-3 text-left">JSS</th>
                    <th class="px-4 py-3 text-left">Kapal</th>
                    <th class="px-4 py-3 text-left">Voyage</th>
                    <th class="px-4 py-3 text-left">Rute</th>
                    <th class="px-4 py-3 text-center">ETD</th>
                    <th class="px-4 py-3 text-center">SLA Pelayaran</th>
                    <th class="px-4 py-3 text-center">Status SLA</th>
                    <th class="px-4 py-3 text-center">Status Jadwal</th>
                    <th class="px-4 py-3 text-left">Alasan Perubahan Jadwal</th>
                    <th class="px-4 py-3 text-left">Pemeriksaan Kapal</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($rows as $r)
                    @php($v = $r->voyage)
                    @php($sla = $v?->sailingSla)

                    <tr class="border-t">
                        <td class="px-4 py-3 font-semibold text-primary-700">{{ $r->jss }}</td>
                        <td class="px-4 py-3">{{ $v?->vessel?->name }}</td>
                        <td class="px-4 py-3">{{ $v?->voyage_no }}</td>
                        <td class="px-4 py-3">{{ $v?->pol?->code }} → {{ $v?->pod?->code }}</td>
                        <td class="px-4 py-3 text-center">{{ optional($v?->etd)->format('d M Y') }}</td>

                        <td class="px-4 py-3 text-center">
                            {{ $sla ? number_format($sla->actual_days, 2) . ' / ' . $sla->target_days . ' hari' : '—' }}
                        </td>

                        <td class="px-4 py-3 text-center">
                            @if ($sla)
                                <span
                                    class="px-2 py-1 rounded-full text-xs font-semibold
                                    {{ $sla->status === 'late' ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700' }}">
                                    {{ $sla->status === 'late' ? 'SLA Tidak Tercapai' : 'SLA Tercapai' }}
                                </span>
                            @else
                                —
                            @endif
                        </td>

                        <td class="px-4 py-3 text-center">
                            @if ($v?->is_delayed)
                                <span
                                    class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs font-semibold">
                                    ETA Mundur
                                </span>
                            @else
                                <span class="px-2 py-1 bg-green-100 text-green-700 rounded-full text-xs font-semibold">
                                    Sesuai Jadwal
                                </span>
                            @endif
                        </td>

                        <td class="px-4 py-3">
                            {{ $v?->is_delayed ? $v?->delay_reason?->label() : '—' }}
                        </td>

                        <td class="px-4 py-3 text-xs">
                            @foreach ($r->vesselChecks as $check)
                                <div class="mb-1 px-2 py-1 rounded {{ $check->status->color() }}">
                                    {{ $check->day_code }} • {{ $check->check_date->format('d M') }} •
                                    {{ $check->status->label() }}
                                </div>
                            @endforeach
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="text-center py-8 text-gray-400">
                            Tidak ada pelayaran pada periode yang dipilih
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
