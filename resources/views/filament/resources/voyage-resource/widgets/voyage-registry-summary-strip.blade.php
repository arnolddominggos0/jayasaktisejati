@php
$counts = $this->getCounts();
@endphp

<div class="flex items-center gap-3 text-xs text-gray-500 mb-2 px-1">
    <span class="font-medium text-gray-600">Active</span>
    <span class="text-gray-900 font-semibold tabular-nums">{{ $counts['active'] }}</span>

    <span class="text-gray-300">|</span>

    <span class="font-medium text-gray-600">Delayed</span>
    <span class="text-gray-900 font-semibold tabular-nums">{{ $counts['delayed'] }}</span>

    <span class="text-gray-300">|</span>

    <span class="font-medium text-gray-600">Closed</span>
    <span class="text-gray-900 font-semibold tabular-nums">{{ $counts['closed'] }}</span>

    <span class="text-gray-300">|</span>

    <span class="font-medium text-gray-600">Archived</span>
    <span class="text-gray-400 font-medium tabular-nums">{{ $counts['archived'] }}</span>
</div>
