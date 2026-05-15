<div class="overflow-x-auto bg-white dark:bg-slate-900 shadow-sm dark:shadow-black/20 rounded-lg border border-gray-200 dark:border-slate-800">
    <table class="min-w-full text-sm text-gray-800 dark:text-slate-200">
        <thead class="bg-gray-100 dark:bg-slate-800 text-gray-700 dark:text-slate-300 uppercase text-xs font-semibold">
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
            <tr class="{{ $loop->even ? 'bg-gray-50 dark:bg-slate-950' : 'bg-white dark:bg-slate-900' }}">
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