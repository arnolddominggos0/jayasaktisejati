<div class="bg-white dark:bg-slate-900 rounded-xl border dark:border-slate-800 overflow-hidden">

 <div class="px-4 py-3 font-semibold border-b dark:border-slate-800 dark:text-white">
 Shipping Schedule Table
 </div>

 @if (empty($rows))
 <div class="p-6 text-sm text-gray-500 dark:text-slate-400 text-center">
 Tidak ada data yang ditemukan
 </div>
 @else
 <div class="overflow-x-auto">
 <table class="w-full text-sm border-collapse dark:text-slate-300">
 <thead>
 <tr class="bg-gray-50 dark:bg-slate-800">
 <th class="border dark:border-slate-800 px-3 py-2 text-left">JSS</th>
 <th class="border dark:border-slate-800 px-3 py-2 text-left">Pelayaran</th>
 <th class="border dark:border-slate-800 px-3 py-2 text-left">Kapal</th>
 <th class="border dark:border-slate-800 px-3 py-2 text-left">Voyage</th>
 <th class="border dark:border-slate-800 px-3 py-2 text-left">Lane</th>
 <th class="border dark:border-slate-800 px-3 py-2 text-left">ETD</th>
 <th class="border dark:border-slate-800 px-3 py-2 text-left">ETA</th>
 </tr>
 </thead>
 <tbody>
 @foreach ($rows as $r)
 <tr class="hover:bg-gray-50 dark:hover:bg-slate-800/40/50">
 <td class="border dark:border-slate-800 px-3 py-2">{{ $r->jss }}</td>
 <td class="border dark:border-slate-800 px-3 py-2">{{ $r->voyage?->vessel?->shippingLine?->name }}</td>
 <td class="border dark:border-slate-800 px-3 py-2">{{ $r->voyage?->vessel?->name }}</td>
 <td class="border dark:border-slate-800 px-3 py-2">{{ $r->voyage?->voyage_no }}</td>
 <td class="border dark:border-slate-800 px-3 py-2">
 {{ $r->voyage?->pol?->code }} → {{ $r->voyage?->pod?->code }}
 </td>
 <td class="border dark:border-slate-800 px-3 py-2">{{ optional($r->voyage?->etd)->format('d M Y H:i') }}</td>
 <td class="border dark:border-slate-800 px-3 py-2">{{ optional($r->voyage?->eta)->format('d M Y H:i') }}</td>
 </tr>
 @endforeach
 </tbody>
 </table>
 </div>
 @endif

</div>
