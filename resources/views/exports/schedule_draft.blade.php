<div class="overflow-x-auto bg-white shadow-sm rounded-lg border border-gray-200">
    <table class="min-w-full text-sm text-gray-800">
        <thead class="bg-gray-100 text-gray-700 uppercase text-xs font-semibold">
            <tr>
                <th class="px-3 py-3 text-center w-10">No</th>
                <th class="px-3 py-3 text-center">ETD</th>
                <th class="px-3 py-3 text-center">ETA</th>
                <th class="px-3 py-3 text-left">Cargo Plan</th>
                <th class="px-3 py-3 text-left">Vessel</th>
                <th class="px-3 py-3 text-center">Vessel Capacity</th>
                <th class="px-3 py-3 text-center">Voyage No</th>
                <th class="px-3 py-3 text-left">JSS</th>
                <th class="px-3 py-3 text-center">Dwelling</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @php($sumCargo = 0)
            @foreach ($rows as $r)
            @php($sumCargo += (int)($r['cargo_plan'] !== '' ? $r['cargo_plan'] : 0))
            <tr class="{{ $loop->even ? 'bg-gray-50' : 'bg-white' }}">
                <td class="px-3 py-2 text-center font-medium">{{ $loop->iteration }}</td>
                <td class="px-3 py-2 text-center">{{ $r['etd'] }}</td>
                <td class="px-3 py-2 text-center">{{ $r['eta'] }}</td>
                <td class="px-3 py-2">{{ $r['cargo_plan'] !== '' ? $r['cargo_plan'] : '-' }}</td>
                <td class="px-3 py-2">{{ $r['vessel'] }}</td>
                <td class="px-3 py-2 text-center">{{ $r['vessel_capacity'] !== '' ? $r['vessel_capacity'] : '-' }}</td>
                <td class="px-3 py-2 text-center">{{ $r['voyage_no'] !== '' ? $r['voyage_no'] : '-' }}</td>
                <td class="px-3 py-2">{{ $r['jss'] !== '' ? $r['jss'] : '-' }}</td>
                <td class="px-3 py-2 text-center">{{ $r['dwelling'] !== '' ? $r['dwelling'] : '-' }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="bg-lime-100 font-semibold">
                <td class="px-3 py-2 text-center">#</td>
                <td class="px-3 py-2 text-center" colspan="2">TOTAL</td>
                <td class="px-3 py-2">{{ $sumCargo }}</td>
                <td class="px-3 py-2" colspan="5"></td>
            </tr>
        </tfoot>
    </table>
</div>