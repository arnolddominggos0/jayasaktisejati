@props(['status', 'label'])

@php
use App\Enums\SlaStatus;

$ok = $status instanceof SlaStatus && $status->value === 'ontime';
$color = $ok
    ? 'bg-emerald-100 text-emerald-700 border-emerald-200'
    : 'bg-red-100 text-red-700 border-red-200';
$text = $ok ? 'OK' : 'NG';
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center px-2 py-1 rounded-md border text-[10px] font-black {$color}"]) }}>
    {{ $text }}
</span>
