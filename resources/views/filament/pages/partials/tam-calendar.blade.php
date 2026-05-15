<div class="bg-white dark:bg-slate-900/80 rounded-xl border border-gray-200 dark:border-slate-800 dark:shadow-sm dark:shadow-black/10 overflow-hidden">

 <div class="px-6 py-4 border-b border-gray-200 dark:border-slate-800 flex justify-between items-center">
 <div class="font-semibold text-sm tracking-wide">
 Kalender Jadwal Pelayaran — {{ $calendar['month_label'] }}
 </div>

 <div class="text-xs text-gray-500 dark:text-slate-400">
 Hari merah = weekend
 </div>
 </div>

 <div class="overflow-x-auto">
 <table class="min-w-[1500px] w-full border-collapse text-[11px]">

 <thead class="sticky top-0 z-30 bg-white dark:bg-slate-900">
 <tr class="bg-gray-50 dark:bg-slate-950">
 <th class="sticky left-0 z-20 bg-white dark:bg-slate-900 border-r border-gray-200 dark:border-slate-800 px-4 py-2 w-40 text-left font-semibold">
 Lane
 </th>

 @foreach ($calendar['days'] as $day)
 <th
 class="px-2 py-2 text-center border-r border-gray-200 dark:border-slate-800
 {{ $day['isWeekend'] ? 'bg-rose-50 dark:bg-rose-950/30 text-rose-500' : 'text-gray-700 dark:text-slate-300' }}
 {{ $day['isToday'] ? 'bg-blue-50 dark:bg-blue-950/30 border-b-2 border-blue-600' : '' }}">

 <div class="text-[9px] uppercase tracking-wide">
 {{ $day['dow'] }}
 </div>

 <div class="font-semibold">
 {{ $day['n'] }}
 </div>
 </th>
 @endforeach

 </tr>
 </thead>

 <tbody class="divide-y">

 @foreach ($calendar['lanes'] as $laneKey => $laneLabel)

 <tr class="align-top">

 <td class="sticky left-0 z-10 bg-white dark:bg-slate-900 border-r border-gray-200 dark:border-slate-800 px-4 py-4 font-medium text-gray-700 dark:text-slate-300">
 {{ $laneLabel }}
 </td>

 @for ($i = 1; $i <= $calendar['days_count']; $i++)

 <td class="border-r border-gray-200 dark:border-slate-800 p-2 h-24 align-top">

 @foreach ($calendar['bucket'][$laneKey][$i] as $chip)

 @php
 $status = $chip['status'];
 $delayLabel = $chip['delay_label'] ?? null;
 $severity = $chip['severity'] ?? null;

 $severityBorder = match ($severity) {
 'minor' => 'ring-2 ring-yellow-400',
 'moderate' => 'ring-2 ring-orange-400',
 'major' => 'ring-2 ring-red-500',
 default => '',
 };
 @endphp

 <div
 class="mb-2 rounded-lg px-2 py-2 text-[11px] font-semibold
 {{ $status->color() }}
 {{ $severityBorder }}">

 <div class="truncate text-[12px] font-bold">
 {{ $chip['vessel'] }}
 </div>

 <div class="text-[10px] opacity-90">
 {{ $chip['voyage_no'] }}
 </div>

 @if ($delayLabel)
 <div class="text-[10px] font-bold mt-1">
 {{ $delayLabel }}
 </div>
 @endif

 </div>

 @endforeach

 </td>

 @endfor

 </tr>

 @endforeach

 </tbody>

 </table>
 </div>

 <div class="px-6 py-3 border-t border-gray-200 dark:border-slate-800 text-xs flex flex-wrap gap-6 text-gray-600 dark:text-slate-400">

 <div class="flex items-center gap-2">
 <span class="w-3 h-3 bg-green-600 rounded-full"></span>
 Selesai
 </div>

 <div class="flex items-center gap-2">
 <span class="w-3 h-3 bg-blue-600 rounded-full"></span>
 Berlayar
 </div>

 <div class="flex items-center gap-2">
 <span class="w-3 h-3 bg-red-600 rounded-full"></span>
 Terlambat
 </div>

 <div class="flex items-center gap-2">
 <span class="w-3 h-3 bg-gray-600 rounded-full"></span>
 Terjadwal
 </div>

  <div class="flex items-center gap-2">
  <span class="w-3 h-3 bg-yellow-400 rounded-full"></span>
  Terlambat Ringan (1–2 Hari)
  </div>

  <div class="flex items-center gap-2">
  <span class="w-3 h-3 bg-orange-400 rounded-full"></span>
  Terlambat Sedang (3–5 Hari)
  </div>

  <div class="flex items-center gap-2">
  <span class="w-3 h-3 bg-red-500 rounded-full"></span>
  Terlambat Berat (&gt;5 Hari)
  </div>

 </div>

</div>