@props([
    'state' => 'default',
    'label' => null,
])

@php
use App\Supports\OperationalUi;

$classes = OperationalUi::indicatorClasses($state);
$display = $label ?? match ($state) {
    'success' => '✓',
    'warning' => '⚠',
    'danger'  => '✕',
    default   => '—',
};
@endphp

<div {{ $attributes->merge(['class' => "inline-flex items-center justify-center w-8 h-8 rounded-xl text-sm font-black {$classes}"]) }}>
    {{ $display }}
</div>
