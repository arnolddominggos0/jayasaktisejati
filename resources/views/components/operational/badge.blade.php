@props([
    'label',
    'color' => 'gray',
    'size' => 'sm',
])

@php
$sizeClass = match ($size) {
    'xs'  => 'px-1.5 py-0.5 text-[10px]',
    'sm'  => 'px-2 py-0.5 text-[11px]',
    'md'  => 'px-2.5 py-1 text-xs',
    default => 'px-2 py-0.5 text-[11px]',
};
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center rounded border font-semibold uppercase tracking-wide {$sizeClass} {$color}"]) }}>
    {{ $label }}
</span>
