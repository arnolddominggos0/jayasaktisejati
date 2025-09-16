@php
    $order = \App\Enums\TrackStatus::order();
    $last = $getRecord()->latestTrack?->status?->value ?? ($getRecord()->latestTrack?->status ?? null);
    $idx = $last ? array_search(\App\Enums\TrackStatus::from($last), $order, true) : -1;
@endphp
<div class="min-w-[420px] flex items-center gap-2">
    @foreach ($order as $i => $step)
        <div class="flex flex-col items-center w-8">
            <div
                class="w-6 h-6 rounded-full flex items-center justify-center text-[10px]
                {{ $i <= $idx ? 'bg-primary-600 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-300' }}">
                {{ $i + 1 }}
            </div>
        </div>
        @if ($i < count($order) - 1)
            <div class="h-0.5 flex-1 {{ $i < $idx ? 'bg-primary-600' : 'bg-gray-200 dark:bg-gray-700' }}"></div>
        @endif
    @endforeach
</div>
