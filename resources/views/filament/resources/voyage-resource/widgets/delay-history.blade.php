<div class="space-y-4">
    @if($logs->isEmpty())
        <div class="text-sm text-gray-500">
            Tidak ada riwayat perubahan jadwal.
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm border rounded-lg">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-3 py-2 text-left">Tanggal</th>
                        <th class="px-3 py-2 text-left">ETD Lama</th>
                        <th class="px-3 py-2 text-left">ETD Baru</th>
                        <th class="px-3 py-2 text-left">ETA Lama</th>
                        <th class="px-3 py-2 text-left">ETA Baru</th>
                        <th class="px-3 py-2 text-left">Alasan</th>
                        <th class="px-3 py-2 text-left">Diubah Oleh</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($logs as $log)
                        <tr class="border-t">
                            <td class="px-3 py-2">
                                {{ $log->created_at?->format('d M Y H:i') }}
                            </td>

                            <td class="px-3 py-2">
                                {{ optional($log->old_etd)->format('d M Y H:i') }}
                            </td>

                            <td class="px-3 py-2 font-semibold text-danger-600">
                                {{ optional($log->new_etd)->format('d M Y H:i') }}
                            </td>

                            <td class="px-3 py-2">
                                {{ optional($log->old_eta)->format('d M Y H:i') }}
                            </td>

                            <td class="px-3 py-2 font-semibold text-danger-600">
                                {{ optional($log->new_eta)->format('d M Y H:i') }}
                            </td>

                            <td class="px-3 py-2">
                                {{ $log->reason }}
                            </td>

                            <td class="px-3 py-2">
                                {{ $log->changed_by }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
