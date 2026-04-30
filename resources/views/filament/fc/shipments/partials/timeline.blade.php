@php
use App\Enums\TrackStatus;
use Illuminate\Support\Facades\Storage;

if ($items instanceof \Closure) {
$items = $items();
}

$tracks = ($items ?? collect())->sortBy('tracked_at')->values();
$latestVal = optional($tracks->last())->status?->value;

/** @var \BackedEnum[] $order */
$order = TrackStatus::orderSea();

// For progress calculation, use last non-terminal track when current is Hold/Cancelled
$progressVal = $latestVal;
if (in_array($progressVal, ['hold', 'cancelled'], true)) {
    $prev = $tracks->reverse()->first(fn($t) => ! in_array($t->status?->value, ['hold', 'cancelled'], true));
    $progressVal = $prev?->status?->value;
}

$fmt = function ($dt) {
if (! $dt) {
return '—';
}

return $dt
->timezone(config('app.timezone'))
->format('d M Y, H:i');
};

$label = fn($s) => $s?->label() ?? (is_string($s) ? $s : '-');

$icon = function (string $v) {
return match ($v) {
'delivered' => 'heroicon-m-check-badge',
'cancelled' => 'heroicon-m-x-circle',
'hold' => 'heroicon-m-pause-circle',
'pickup' => 'heroicon-m-truck',
'handover' => 'heroicon-m-building-office',
'stuffing' => 'heroicon-m-wrench-screwdriver',
'delivery_to_port' => 'heroicon-m-arrow-up-right',
'stacking' => 'heroicon-m-rectangle-group',
'unit_loading' => 'heroicon-m-arrow-up-tray',
'onship' => 'heroicon-m-rocket-launch',
'vessel_depart' => 'heroicon-m-paper-airplane',
'vessel_arrival' => 'heroicon-m-flag',
'unloading' => 'heroicon-m-arrow-down-tray',
'delivery_to_customer' => 'heroicon-m-user',
default => 'heroicon-m-clock',
};
};

// Warna kompatibel Tailwind lama (blue/green/gray)
$dot = function (bool $isDone, bool $isCurrent) {
if ($isCurrent) return 'bg-blue-600 ring-4 ring-blue-200 animate-pulse';
return $isDone ? 'bg-green-600 ring-2 ring-green-200' : 'bg-gray-300 ring-2 ring-gray-200';
};

$chip = function (bool $isDone, bool $isCurrent) {
if ($isCurrent) return 'text-blue-900 bg-blue-100 ring-1 ring-blue-300 shadow-sm';
if ($isDone) return 'text-green-800 bg-green-100 ring-1 ring-green-200';
return 'text-gray-700 bg-gray-100 ring-1 ring-gray-200';
};

$iconTint = function (bool $isDone, bool $isCurrent) {
if ($isCurrent) return 'text-blue-600';
if ($isDone) return 'text-green-600';
return 'text-gray-400';
};

$describeStep = function ($hit, bool $curr, string $val) {
if (!empty($hit?->note)) {
return ['text' => $hit->note, 'class' => 'text-gray-900'];
}

if ($hit) {
if ($curr) {
return ['text' => 'Sedang berlangsung.', 'class' => 'text-blue-800 font-medium'];
}

$defaultDone = match ($val) {
'pickup' => 'Penjemputan selesai.',
'handover' => 'Sudah serah terima di depo.',
'stuffing' => 'Stuffing & segel telah dilakukan.',
'delivery_to_port' => 'Barang tiba di pelabuhan.',
'stacking' => 'Stacking di terminal selesai.',
'unit_loading' => 'Kontainer sudah dimuat ke kapal.',
'onship' => 'Sedang berlayar.',
'vessel_depart' => 'Kapal telah berangkat.',
'vessel_arrival' => 'Kapal sudah tiba.',
'unloading' => 'Barang berhasil dibongkar.',
'delivery_to_customer' => 'Dalam proses antar ke customer.',
'delivered' => 'Pengiriman selesai.',
default => 'Selesai.',
};

return ['text' => $defaultDone, 'class' => 'text-green-700'];
}

return ['text' => 'Menunggu proses ini.', 'class' => 'text-gray-500'];
};

$totalSteps = count($order);
$currentIndex = collect($order)->search(fn($s) => $s->value === $progressVal);
$doneCount = $currentIndex === false ? 0 : $currentIndex + 1;
$progressPct = $totalSteps > 0 ? intval(round(($doneCount / $totalSteps) * 100)) : 0;
@endphp

<section class="rounded-xl border border-gray-200 bg-white shadow-sm">
    <header class="px-4 sm:px-6 py-3 border-b border-gray-100">
        <div class="flex items-center justify-between gap-2">
            <h3 class="text-sm font-semibold text-gray-900">Timeline Status</h3>

            @if ($latestVal)
            @php $latest = $tracks->last(); @endphp
            <div class="hidden sm:flex items-center gap-2 text-xs">
                <span class="inline-flex items-center gap-1 rounded-md bg-gray-50 px-2 py-1 ring-1 ring-gray-200 text-gray-700">
                    <x-filament::icon :icon="$icon($latest->status->value)" class="h-4 w-4 text-gray-400" />
                    {{ $label($latest->status ?? null) }}
                </span>
                <span class="text-gray-400">•</span>
                <time class="text-gray-500">{{ $fmt($latest->tracked_at) }}</time>
            </div>
            @endif
        </div>

        <div class="mt-3">
            <div class="h-1.5 w-full rounded-full bg-gray-100 overflow-hidden">
                <div class="h-full rounded-full bg-blue-500 bg-gradient-to-r from-green-500 via-green-400 to-blue-500" style="width: {{ $progressPct }}%"></div>
            </div>
            <div class="mt-1.5 flex items-center justify-between text-[11px] text-gray-500">
                <span>{{ $doneCount }}/{{ $totalSteps }} tahap</span>
                <span>{{ $progressPct }}%</span>
            </div>
        </div>
    </header>

    <div class="relative">
        <div class="absolute left-8 sm:left-10 top-0 bottom-0 w-px bg-gray-200"></div>

        <ul class="m-0 p-0 list-none">
            @foreach ($order as $idx => $step)
            @php
            $val = (string) $step->value;
            $hit = $tracks->firstWhere('status.value', $val);
            $done = (bool) $hit;
            $curr = $latestVal === $val;

            $hasNext = $idx < ($totalSteps - 1);
                $connector=$curr || $done ? 'from-green-400 to-green-200' : 'from-gray-200 to-gray-200' ;
                $rowWrap=$curr ? 'bg-blue-50 ring-1 ring-blue-300' : ($done ? 'bg-white' : 'hover:bg-gray-50' );

                $d=$describeStep($hit, $curr, $val);
                @endphp

                <li class="relative">
                @if ($hasNext)
                <div class="absolute left-8 sm:left-10 top-9 bottom-[-8px] w-px bg-gradient-to-b {{ $connector }}"></div>
                @endif

                <div class="rounded-lg {{ $rowWrap }} transition-colors">
                    <div class="grid grid-cols-[2rem_1fr_auto] sm:grid-cols-[2.5rem_1fr_12rem] items-start gap-3 sm:gap-4 px-3 sm:px-6 py-2.5 sm:py-3">
                        <div class="flex items-start justify-center">
                            <div class="mt-1 h-3 w-3 rounded-full {{ $dot($done, $curr) }}"></div>
                        </div>

                        <div class="min-w-0">
                            <div class="flex items-start gap-3">
                                <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl ring-1 ring-gray-200 bg-white shadow-sm">
                                    <x-filament::icon :icon="$icon($val)" class="h-5 w-5 {{ $iconTint($done, $curr) }}" />
                                </span>

                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                                        <span class="inline-flex items-center rounded-md px-2 py-0.5 text-[11px] font-medium {{ $chip($done, $curr) }}">
                                            {{ $step->label() }}
                                        </span>

                                        @if ($curr)
                                        <span class="inline-flex items-center rounded-md bg-blue-100 text-blue-900 ring-1 ring-blue-300 px-1.5 py-0.5 text-[10px] font-semibold">
                                            Berlangsung
                                        </span>
                                        @endif

                                        @if ($hit?->location)
                                        <span class="text-[11px] text-gray-500 italic truncate max-w-[18rem]">{{ $hit->location }}</span>
                                        @endif

                                        @if ($hit?->user?->name)
                                        <span class="text-[11px] text-gray-500">oleh {{ $hit->user->name }}</span>
                                        @endif
                                    </div>

                                    <p class="mt-1 text-[13px] leading-6 {{ $d['class'] }} line-clamp-2">
                                        {{ $d['text'] }}
                                    </p>

                                    @if ($hit && !empty($hit->checkseet) && is_array($hit->checkseet))
                                    <div class="mt-2 space-y-1.5">
                                        @foreach ($hit->checkseet as $item)
                                        @php
                                            $csStatus = $item['checkseet_status'] ?? '-';
                                            $csBadge = match ($csStatus) {
                                                'ok' => 'bg-green-100 text-green-800 ring-green-200',
                                                'ng' => 'bg-red-100 text-red-800 ring-red-200',
                                                default => 'bg-gray-100 text-gray-700 ring-gray-200',
                                            };
                                        @endphp
                                        <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs">
                                            <span class="inline-flex items-center rounded px-1.5 py-0.5 ring-1 {{ $csBadge }}">
                                                {{ strtoupper($csStatus) }}
                                            </span>
                                            <span class="text-gray-700">
                                                {{ $item['model'] ?? '—' }}
                                                @if (!empty($item['no_rangka']))
                                                <span class="text-gray-400">· {{ $item['no_rangka'] }}</span>
                                                @endif
                                            </span>
                                        </div>
                                        @endforeach
                                    </div>
                                    @endif

                                    @if ($hit && !empty($hit->attachments) && is_array($hit->attachments))
                                    <div class="mt-2 flex flex-wrap gap-2">
                                        @foreach ($hit->attachments as $att)
                                        @if (is_string($att) && $att)
                                        <a href="{{ Storage::url($att) }}" target="_blank" class="inline-flex items-center gap-1 rounded-md bg-gray-50 px-2 py-1 text-[11px] text-gray-600 ring-1 ring-gray-200 hover:bg-gray-100">
                                            <x-filament::icon icon="heroicon-m-paper-clip" class="h-3.5 w-3.5 text-gray-400" />
                                            Lampiran
                                        </a>
                                        @endif
                                        @endforeach
                                    </div>
                                    @endif

                                    <div class="mt-0.5 text-xs text-gray-500 sm:hidden">
                                        {{ $hit?->tracked_at ? $fmt($hit->tracked_at) : '' }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="hidden sm:flex items-start justify-end">
                            <time class="text-xs text-gray-500">{{ $hit?->tracked_at ? $fmt($hit->tracked_at) : '' }}</time>
                        </div>
                    </div>
                </div>

                <div class="mx-3 sm:mx-6 h-px bg-gray-50"></div>
                </li>
                @endforeach
        </ul>
    </div>
</section>