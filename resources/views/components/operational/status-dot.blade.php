@props(['color', 'label'])

<div class="flex items-center gap-1">
    <span class="w-1.5 h-1.5 rounded-full {{ $color }}"></span>
    {{ $label }}
</div>
