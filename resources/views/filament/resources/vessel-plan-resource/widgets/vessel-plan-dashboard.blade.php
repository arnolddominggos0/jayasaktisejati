<x-filament::section>
 <x-slot name="heading">
 Dashboard Jadwal Final (Operasional)
 </x-slot>

 <x-slot name="description">
 Ringkasan performa jadwal kapal setelah finalisasi
 </x-slot>

 <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
 <div class="rounded-xl border dark:border-slate-800 bg-white dark:bg-slate-900 p-4">
 <div class="text-xs text-gray-500 dark:text-slate-400 uppercase">Total Voyage</div>
 <div class="text-2xl font-bold dark:text-white">{{ $totalVoyages }}</div>
 </div>

 <div class="rounded-xl border dark:border-slate-800 bg-white dark:bg-slate-900 p-4">
 <div class="text-xs text-gray-500 dark:text-slate-400 uppercase">Total Cargo Plan</div>
 <div class="text-2xl font-bold dark:text-white">
 {{ number_format($totalCargoPlan ?? 0) }}
 </div>
 </div>

 <div class="rounded-xl border dark:border-slate-800 bg-white dark:bg-slate-900 p-4">
 <div class="text-xs text-gray-500 dark:text-slate-400 uppercase">Avg Dwelling</div>
 <div class="text-2xl font-bold dark:text-white">
 {{ $avgDwelling }} hari
 </div>
 </div>

 <div class="rounded-xl border dark:border-slate-800 bg-white dark:bg-slate-900 p-4">
 <div class="text-xs text-gray-500 dark:text-slate-400 uppercase">Voyage Delay</div>
 <div class="text-2xl font-bold text-red-600 dark:text-red-400">
 {{ $delayCount }}
 </div>
 </div>
 </div>
</x-filament::section>
