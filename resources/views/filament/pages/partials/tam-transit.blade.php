<div class="space-y-6">

 @php
 $sailingVoyages = $rows->filter(fn($v) =>
 $v->operational_status_enum->value === 'sailing'
 );
 @endphp

 @forelse($sailingVoyages as $v)

 <div class="bg-white dark:bg-slate-900 border rounded-xl p-6 space-y-4">

 <div class="flex justify-between">
 <div>
 <div class="font-semibold text-lg">
 {{ $v->vessel?->name }} — {{ $v->voyage_no }}
 </div>
 <div class="text-sm text-gray-500 dark:text-slate-400">
 {{ $v->pol?->code }} → {{ $v->pod?->code }}
 </div>
 </div>

 <div class="text-sm text-gray-600 dark:text-slate-400">
 ATD: {{ optional($v->atd_at)->format('d M Y') }}
 </div>
 </div>

 <div class="grid grid-cols-5 gap-4 text-xs">

 @foreach ($v->milestones as $code => $date)
 <div class="bg-gray-50 dark:bg-slate-950 border rounded-lg p-3">
 <div class="font-semibold uppercase">
 {{ strtoupper($code) }}
 </div>
 <div class="text-gray-600 dark:text-slate-400">
 {{ $date->format('d M Y') }}
 </div>
 </div>
 @endforeach

 </div>

 </div>

 @empty
 <div class="bg-white dark:bg-slate-900 border rounded-xl p-6 text-center text-gray-500 dark:text-slate-400">
 Tidak ada kapal sailing.
 </div>
 @endforelse

</div>