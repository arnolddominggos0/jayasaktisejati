@php
/** @var array{present:int,target:int,percent:int,tone:string} $state */
$state = $getState() ?? ['present' => 0, 'target' => 0, 'percent' => 0, 'tone' => 'gray'];

$present   = max(0, (int) ($state['present'] ?? 0));
$target    = max(0, (int) ($state['target'] ?? 0));
$rawPct    = $target > 0 ? min(100, max(0, (int) ($state['percent'] ?? 0))) : 0;
$pctForBar = ($target > 0 && $rawPct === 0) ? 2 : $rawPct;

$tone = $state['tone'] ?? 'gray';
$bg   = match ($tone) {
    'emerald' => 'bg-emerald-500',
    'amber'   => 'bg-amber-500',
    'rose'    => 'bg-rose-500',
    default   => 'bg-slate-300',
};
$pctColor = match ($tone) {
    'emerald' => 'text-emerald-700',
    'amber'   => 'text-amber-700',
    'rose'    => 'text-rose-700',
    default   => 'text-slate-500',
};
@endphp

{{--
    Single-row layout: COUNT  [BAR]  PCT
    whitespace-nowrap on the text spans prevents wrapping in narrow columns.
    flex-1 on the bar div takes remaining space between the two labels.
--}}
<div class="flex items-center gap-2"
     title="Hadir {{ $present }} / Target {{ $target }} MP ({{ $rawPct }}%)">

    <span class="shrink-0 text-xs font-semibold tabular-nums text-slate-700 whitespace-nowrap">
        {{ $present }}&thinsp;/&thinsp;{{ $target }}&thinsp;MP
    </span>

    <div class="h-2 min-w-[3rem] flex-1 overflow-hidden rounded-full bg-slate-200"
         role="progressbar"
         aria-valuemin="0"
         aria-valuemax="100"
         aria-valuenow="{{ $rawPct }}">
        <div class="h-2 rounded-full {{ $bg }} transition-all"
             style="width: {{ $pctForBar }}%"></div>
    </div>

    <span class="shrink-0 text-xs font-semibold tabular-nums whitespace-nowrap {{ $pctColor }}">
        {{ $rawPct }}%
    </span>
</div>
