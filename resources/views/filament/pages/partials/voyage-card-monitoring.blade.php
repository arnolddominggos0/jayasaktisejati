@php

 $overdue = $v->milestones->where('is_overdue', true)->count();
 $dueToday = $v->milestones->where('is_due_today', true)->count();

 $border = match (true) {
 $overdue > 0 => 'border-red-400',
 $dueToday > 0 => 'border-orange-400',
 default => 'border-gray-200 dark:border-slate-800',
 };

 $sailingDuration = null;
 $sailingProgress = null;

 if ($v->atd_at) {

 $days = $v->atd_at->diffInDays(now());

 if ($days > 0) {
 $sailingDuration = $days . ' Hari';
 $sailingProgress = "Day {$days} / 12";
 } else {
 $sailingDuration = '1 Hari';
 $sailingProgress = "Day 1 / 12";
 }

 }

 $milestones = $v->milestones
 ->sortBy(fn ($m) => (int) str_replace('d', '', $m->code));

@endphp


<div class="bg-white dark:bg-slate-900/80 border {{ $border }} rounded-xl p-4 mb-4 dark:shadow-sm dark:shadow-black/10 transition-colors duration-150">

 <div class="flex justify-between items-start">

 <div>

 <div class="font-semibold text-sm">
 {{ $v->vessel?->name }} — {{ $v->voyage_no }}
 </div>

 <div class="text-xs text-gray-500 dark:text-slate-400 mt-1">
 {{ $v->pol?->code }} → {{ $v->pod?->code }}
 </div>


 <div class="text-xs text-gray-600 dark:text-slate-400 mt-2 space-x-4">

 <span>
 ATB: {{ optional($v->atb_at)->format('d M H:i') ?? '-' }}
 </span>

 <span>
 Closing: {{ optional($v->closing_at)->format('d M H:i') ?? '-' }}
 </span>

 <span>
 ATD: {{ optional($v->atd_at)->format('d M H:i') ?? '-' }}
 </span>

 <span>
 ETA: {{ optional($v->eta)->format('d M H:i') ?? '-' }}
 </span>

 @if ($sailingDuration)
 <span>
 Berlayar {{ $sailingDuration }}
 </span>
 @endif

 </div>


 @if ($sailingProgress)
 <div class="text-xs text-blue-600 dark:text-blue-400 mt-1 font-semibold">
 {{ $sailingProgress }}
 </div>
 @endif


 @if ($v->eta_overdue)
 <div class="inline-block mt-2 px-2 py-0.5 text-[11px] bg-red-100 text-red-700 dark:text-red-300 rounded">
 ETA Terlewati
 </div>
  @elseif($v->sailing_risk)
  <div class="inline-block mt-2 px-2 py-0.5 text-[11px] bg-orange-100 text-orange-700 rounded">
  ETA &lt; 1 Hari
  </div>
  @endif


 @if ($v->cargo_plan)
 <div class="text-xs text-gray-600 dark:text-slate-400 mt-2">
 Cargo Plan: {{ number_format($v->cargo_plan) }}
 </div>
 @endif


 @if ($v->delay_reason)
 <div class="inline-block mt-2 px-2 py-0.5 text-[11px] bg-gray-100 dark:bg-slate-800 text-gray-700 dark:text-slate-300 rounded">
 Reason: {{ $v->delay_reason->label() }}
 </div>
 @endif

 </div>


 <div class="text-right text-xs space-y-1">

 @if ($overdue > 0)
 <div class="text-red-600 dark:text-red-400 font-semibold">
 {{ $overdue }} laporan belum diinput
 </div>
 @endif

 @if ($dueToday > 0)
 <div class="text-orange-600 font-semibold">
 {{ $dueToday }} jatuh tempo hari ini
 </div>
 @endif

 @if ($overdue === 0 && $dueToday === 0)
 <div class="text-green-600 dark:text-green-400 font-semibold">
 Laporan aman
 </div>
 @endif

 </div>

 </div>



 {{-- GRID MILESTONE --}}
 <div class="grid grid-cols-6 gap-3 mt-4 text-xs">

 @foreach ($milestones as $m)

@php

 if ($m->actual_date) {

 $iconName = 'heroicon-o-check-circle';
 $color = 'bg-green-100 text-green-700 dark:text-green-300 border border-green-200 dark:border-green-800';

 } elseif ($m->is_overdue) {

 $iconName = 'heroicon-o-x-mark';
 $color = 'bg-red-100 text-red-700 dark:text-red-300 border border-red-200 dark:border-red-800';

 } elseif ($m->is_due_today) {

 $iconName = 'heroicon-o-clock';
 $color = 'bg-orange-100 text-orange-700 border border-orange-200';

 } else {

 $iconName = null;
 $color = 'bg-gray-100 dark:bg-slate-800 text-gray-600 dark:text-slate-400 border border-gray-200 dark:border-slate-800';

 }

 @endphp


 <button
 wire:click="showMilestone({{ $m->id }})"
 class="rounded-md py-2 text-center font-semibold {{ $color }} transition-colors duration-150"
 >

 <div class="uppercase tracking-wide">
 {{ strtoupper($m->code) }}
 </div>

 <div class="mt-1 text-base">
 @if ($iconName)
 <x-filament::icon :icon="$iconName" class="h-5 w-5 inline" />
 @else
 —
 @endif
 </div>

 </button>

 @endforeach

 </div>

</div>