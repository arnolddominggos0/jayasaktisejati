@php
use App\Enums\{ShipmentMode, TrackStatus};

$record = $getRecord();

$modeVal = $record->mode instanceof ShipmentMode ? $record->mode->value : (string) $record->mode;

$order = TrackStatus::orderForMode($modeVal); // <- ganti dari ::order()

    $rawLast=$record->latestTrack?->status ?? null;
    $lastEnum = $rawLast instanceof TrackStatus ? $rawLast
    : TrackStatus::tryFrom((string) ($rawLast?->value ?? $rawLast));

    $isHold = $lastEnum === TrackStatus::Hold;
    $isCancelled = $lastEnum === TrackStatus::Cancelled;

    $idx = -1;
    if ($lastEnum && ! $isHold && ! $isCancelled) {
    $pos = array_search($lastEnum, $order, true);
    $idx = ($pos === false) ? -1 : $pos;
    }
    @endphp

    <div class="min-w-[420px] flex items-center gap-2">
        @foreach ($order as $i => $step)
        <div class="flex flex-col items-center w-8">
            <div
                class="w-6 h-6 rounded-full flex items-center justify-center text-[10px]
                {{ $i <= $idx ? 'bg-primary-600 text-white' : 'bg-gray-200 dark:bg-slate-700 text-gray-600 dark:text-slate-300' }}">
                {{ $i + 1 }}
            </div>
        </div>
        @if ($i < count($order) - 1)
            <div class="h-0.5 flex-1 {{ $i < $idx ? 'bg-primary-600' : 'bg-gray-200 dark:bg-slate-700' }}">
    </div>
    @endif
    @endforeach

    {{-- Badge terminal bila Hold/Cancelled --}}
    @if ($isHold || $isCancelled)
    <span class="ml-2 px-2 py-0.5 rounded text-xs
            {{ $isCancelled ? 'bg-rose-600 text-white' : 'bg-amber-500 text-black' }}">
        {{ $lastEnum?->label() }}
    </span>
    @endif
    </div>