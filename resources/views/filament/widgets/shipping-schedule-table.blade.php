<div class="bg-white rounded-2xl shadow-sm border overflow-hidden">

    <div class="px-4 py-3 font-semibold border-b">
        Shipping Schedule Table
    </div>

    @if (empty($rows))
        <div class="p-6 text-sm text-gray-500 text-center">
            Tidak ada data yang ditemukan
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm border-collapse">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="border px-3 py-2 text-left">JSS</th>
                        <th class="border px-3 py-2 text-left">Pelayaran</th>
                        <th class="border px-3 py-2 text-left">Kapal</th>
                        <th class="border px-3 py-2 text-left">Voyage</th>
                        <th class="border px-3 py-2 text-left">Lane</th>
                        <th class="border px-3 py-2 text-left">ETD</th>
                        <th class="border px-3 py-2 text-left">ETA</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $r)
                        <tr class="hover:bg-gray-50">
                            <td class="border px-3 py-2">{{ $r->jss }}</td>
                            <td class="border px-3 py-2">{{ $r->voyage?->vessel?->shippingLine?->name }}</td>
                            <td class="border px-3 py-2">{{ $r->voyage?->vessel?->name }}</td>
                            <td class="border px-3 py-2">{{ $r->voyage?->voyage_no }}</td>
                            <td class="border px-3 py-2">
                                {{ $r->voyage ? \App\Supports\BusinessRouteResolver::forVoyage($r->voyage) : '—' }}
                            </td>
                            <td class="border px-3 py-2">{{ optional($r->voyage?->etd)->format('d M Y H:i') }}</td>
                            <td class="border px-3 py-2">{{ optional($r->voyage?->eta)->format('d M Y H:i') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

</div>
