@php
/** @var array{present:int,target:int,percent:int,tone:string} $state */
$state = $getState() ?? ['present' => 0, 'target' => 0, 'percent' => 0, 'tone' => 'gray'];

$present = max(0, (int) ($state['present'] ?? 0));
$target = max(0, (int) ($state['target'] ?? 0));
$rawPct = $target > 0 ? min(100, max(0, (int) ($state['percent'] ?? 0))) : 0;

$pctForBar = $target > 0 && $rawPct === 0 ? 2 : $rawPct;

$tone = $state['tone'] ?? 'gray';
$bg = match ($tone) {
'emerald' => 'bg-emerald-500',
'amber' => 'bg-amber-500',
'rose' => 'bg-rose-500',
default => 'bg-slate-300',
};
@endphp

<div class="flex items-center gap-2"
    title="Hadir {{ $present }} / Target {{ $target }}">
    <div class="w-28 h-2 rounded bg-slate-200 dark:bg-slate-700 overflow-hidden"
        role="progressbar"
        aria-valuemin="0"
        aria-valuemax="100"
        aria-valuenow="{{ $rawPct }}">
        <div class="h-2 {{ $bg }}"
            style="width: <?= (int) $pctForBar ?>%"></div>
    </div>
    <span class="text-xs font-medium text-slate-700 dark:text-slate-300">{{ $present }}/{{ $target }}</span>
</div>