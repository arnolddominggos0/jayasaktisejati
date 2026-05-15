@php
 $status = $v->operational_status_enum;
@endphp

<div class="bg-white dark:bg-slate-900/80 rounded-xl p-4 border border-gray-200 dark:border-slate-800 dark:shadow-sm dark:shadow-black/10 flex justify-between items-start">

 <div>

 <div class="font-semibold text-base">
 {{ $v->vessel?->name }} — {{ $v->voyage_no }}
 </div>

 <div class="text-sm text-gray-500 dark:text-slate-400">
 {{ $v->pol?->code }} → {{ $v->pod?->code }}
 </div>

 @if ($v->overdue_days)
 <div class="text-sm text-red-600 dark:text-red-400 font-bold mt-1">
 TERLAMBAT {{ $v->overdue_days }} HARI
 </div>
 @endif

  @if ($v->sailing_risk)
  <div class="text-sm text-orange-600 font-semibold mt-1 flex items-center gap-1">
  <x-heroicon-o-exclamation-triangle class="w-4 h-4" />
  ETA kurang dari 1 Hari
  </div>
  @endif

 @if ($v->eta_overdue)
 <div class="text-sm text-red-600 dark:text-red-400 font-semibold mt-1">
 ETA Terlewati
 </div>
 @endif

 </div>

 <div class="text-right text-sm space-y-1">

 <div>ETD: {{ optional($v->etd)->format('d M H:i') ?? '-' }}</div>
 <div>ETA: {{ optional($v->eta)->format('d M H:i') ?? '-' }}</div>

 <span class="px-2 py-1 text-xs rounded {{ $status->color() }}">
 {{ $status->label() }}
 </span>

 </div>

</div>