@props(['severity' => 'normal'])

@php
use App\Supports\OperationalUi;
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold border whitespace-nowrap ' . OperationalUi::severityBadge($severity)]) }}>
    {{ OperationalUi::severityLabel($severity) }}
</span>
