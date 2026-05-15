 <div class="space-y-6">

 <div class="space-y-3">

 <div class="text-sm font-semibold text-gray-600 dark:text-slate-400 uppercase tracking-wide">
 Ringkasan Pencapaian
 </div>

 <div class="grid grid-cols-3 gap-4">

 @foreach ([
 'otd' => 'Ketepatan Berangkat (OTD)',
 'ota' => 'Ketepatan Tiba (OTA)',
 'otb' => 'Ketepatan Sandar (OTB)',
 ] as $key => $label)
 @php
 $okPercent = $achievement[$key]['ok_percent'] ?? 0;
 $ngPercent = $achievement[$key]['ng_percent'] ?? 0;
 $ok = $achievement[$key]['ok'] ?? 0;
 $total = $achievement[$key]['total'] ?? 0;

 $warna = match (true) {
 $okPercent >= 85 => 'text-green-600 dark:text-green-400',
 $okPercent >= 60 => 'text-orange-500',
 default => 'text-red-600 dark:text-red-400',
 };
 @endphp

  <div class="bg-white dark:bg-slate-900/80 rounded-xl border border-gray-200 dark:border-slate-800 dark:shadow-sm dark:shadow-black/10 p-5">

 <div class="text-xs text-gray-500 dark:text-slate-400 uppercase">
 {{ $label }}
 </div>

 <div class="mt-4 flex items-end justify-between">

 <div>
 <div class="text-3xl font-bold {{ $total > 0 ? $warna : 'text-gray-400' }}">
 {{ $total > 0 ? $okPercent . '%' : '—' }}
 </div>

 <div class="text-xs text-gray-500 dark:text-slate-400 mt-1">
 Tepat {{ $ok }} / {{ $total }}
 </div>
 </div>

 <div class="text-sm font-semibold {{ $ngPercent > 0 ? 'text-red-500' : 'text-gray-400' }}">
 Tidak Tepat {{ $ngPercent }}%
 </div>

 </div>

 </div>
 @endforeach

 </div>

 </div>

 <div class="space-y-3">

 <div class="text-sm font-semibold text-gray-600 dark:text-slate-400 uppercase tracking-wide">
 Ringkasan SLA & Operasional
 </div>

 <div class="grid grid-cols-2 gap-4">

 @php
 $slaOk = $achievement['sla']['ok_percent'] ?? 0;
 $slaNg = $achievement['sla']['ng_percent'] ?? 0;
 $slaTotal = $achievement['sla']['total'] ?? 0;

 $slaWarna = match (true) {
 $slaOk >= 85 => 'text-green-600 dark:text-green-400',
 $slaOk >= 60 => 'text-orange-500',
 default => 'text-red-600 dark:text-red-400',
 };
 @endphp

  <div class="bg-white dark:bg-slate-900/80 rounded-xl border border-gray-200 dark:border-slate-800 dark:shadow-sm dark:shadow-black/10 p-5">

 <div class="text-xs text-gray-500 dark:text-slate-400 uppercase">
 Transit SLA
 </div>

 <div class="mt-4 flex items-end justify-between">

 <div>
 <div class="text-3xl font-bold {{ $slaTotal > 0 ? $slaWarna : 'text-gray-400' }}">
 {{ $slaTotal > 0 ? $slaOk . '%' : '—' }}
 </div>

 <div class="text-xs text-gray-500 dark:text-slate-400 mt-1">
 Tercapai {{ $achievement['sla']['ok'] ?? 0 }} / {{ $slaTotal }}
 </div>
 </div>

 <div class="text-sm font-semibold {{ $slaNg > 0 ? 'text-red-500' : 'text-gray-400' }}">
 Tidak Tercapai {{ $slaNg }}%
 </div>

 </div>

 </div>

 <div class="bg-gray-50 dark:bg-slate-950 rounded-xl border border-gray-200 dark:border-slate-800 p-5">

 <div class="text-xs text-gray-500 dark:text-slate-400 uppercase tracking-wide">
 Ringkasan Operasional
 </div>

 <div class="mt-4 space-y-3">

 <div>
 <div class="text-xs text-gray-500 dark:text-slate-400">
 Rata-rata Keterlambatan Berangkat
 </div>
  <div class="text-lg font-semibold text-orange-600">
  {{ ($achievement['rata_rata_delay_berangkat'] ?? 0) > 0
  ? $achievement['rata_rata_delay_berangkat'] . ' Hari'
  : '—' }}
  </div>
 </div>

 <div>
 <div class="text-xs text-gray-500 dark:text-slate-400">
 Penyebab Keterlambatan Terbanyak
 </div>
 <div class="text-lg font-semibold text-red-600 dark:text-red-400">
 {{ $achievement['penyebab_terbanyak'] ?? '—' }}
 </div>
 </div>

 </div>

 </div>

 </div>

 </div>

</div>
