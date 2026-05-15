@php
 $d = $this->getData();
 $kpi = $d['kpi'] ?? [];
 $rows = collect($d['rows'] ?? []);
@endphp
<div class="space-y-6">

 <div class="bg-white dark:bg-slate-900/80 rounded-xl border border-gray-200 dark:border-slate-800 dark:shadow-sm dark:shadow-black/10 p-4">
 <div class="flex items-center justify-between mb-3">
 <div class="text-sm font-semibold dark:text-white">Daftar Voyage (ringkas)</div>
 <div class="text-xs text-gray-500 dark:text-slate-400">Menampilkan {{ $rows->count() }} hasil</div>
 </div>

 <div class="overflow-x-auto">
 <table class="w-full text-sm table-auto">
 <thead>
 <tr class="text-left text-xs text-gray-600 dark:text-slate-400">
 <th class="px-2 py-2">JSS</th>
 <th class="px-2 py-2">Pelayaran</th>
 <th class="px-2 py-2">Kapal</th>
 <th class="px-2 py-2">Voy</th>
 <th class="px-2 py-2">Lane</th>
 <th class="px-2 py-2">ETD</th>
 <th class="px-2 py-2">ATA</th>
 <th class="px-2 py-2">Lead</th>
 <th class="px-2 py-2">SLA</th>
 <th class="px-2 py-2">Plan</th>
 <th class="px-2 py-2">Actual</th>
 <th class="px-2 py-2">ATD Vol</th>
 <th class="px-2 py-2">Delay</th>
 <th class="px-2 py-2">Alasan Delay</th>
 </tr>
 </thead>
 <tbody class="dark:text-slate-300">
 @forelse ($rows as $r)
 <tr class="border-t dark:border-slate-800">
 <td class="px-2 py-2 text-xs"><a class="text-blue-600 dark:text-blue-400 font-semibold" href="#">{{ $r['jss'] }}</a></td>
 <td class="px-2 py-2 text-xs">{{ $r['shipping_line'] }}</td>
 <td class="px-2 py-2 text-xs">{{ $r['vessel'] }}</td>
 <td class="px-2 py-2 text-xs">{{ $r['voyage_no'] }}</td>
 <td class="px-2 py-2 text-xs">{{ $r['lane'] }}</td>
 <td class="px-2 py-2 text-xs">{{ $r['etd'] ? \Illuminate\Support\Carbon::parse($r['etd'])->format('d M') : '-' }}</td>
 <td class="px-2 py-2 text-xs">{{ $r['ata'] ? \Illuminate\Support\Carbon::parse($r['ata'])->format('d M') : '-' }}</td>
 <td class="px-2 py-2 text-xs">{{ $r['lead_time'] ?? '-' }}</td>
 <td class="px-2 py-2 text-xs">{{ $r['sla_status'] ?? '-' }}</td>
 <td class="px-2 py-2 text-xs">{{ $r['plan'] ?? '-' }}</td>
 <td class="px-2 py-2 text-xs">{{ $r['actual'] ?? '-' }}</td>
 <td class="px-2 py-2 text-xs">{{ $r['vol_atd'] ?? '-' }}</td>
 <td class="px-2 py-2 text-xs">{!! $r['delay'] ? '<span class="text-red-600 dark:text-red-400 font-semibold">Yes</span>' : '<span class="text-gray-600 dark:text-slate-400">-</span>' !!}</td>
 <td class="px-2 py-2 text-xs">{{ $r['delay_reason'] ?? '-' }}</td>
 </tr>
 @empty
 <tr>
 <td colspan="14" class="px-2 py-6 text-center text-gray-500 dark:text-slate-400">Tidak ada voyage untuk periode ini.</td>
 </tr>
 @endforelse
 </tbody>
 </table>
 </div>
 </div>
</div>
