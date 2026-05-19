@props(['milestone'])

@php
use App\Supports\OperationalUi;

$chip = OperationalUi::milestoneChip($milestone);
@endphp

<button wire:click="showMilestone({{ $milestone->id }})"
    class="rounded px-1.5 py-0.5 border text-[10px] font-semibold {{ $chip['class'] }} hover:scale-105 transition cursor-pointer"
    title="{{ $chip['title'] }}">
    {{ strtoupper($milestone->code) }}
</button>
