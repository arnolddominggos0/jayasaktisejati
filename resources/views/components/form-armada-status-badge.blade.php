@php
    $label = is_string($label ?? null) ? $label : 'Siap Pakai';
    $color = is_string($color ?? null) ? $color : 'success';
@endphp

<div class="col-span-full">
    <x-filament::badge :color="$color" class="w-full justify-center py-2 text-sm">
        {{ $label }}
    </x-filament::badge>
</div>
