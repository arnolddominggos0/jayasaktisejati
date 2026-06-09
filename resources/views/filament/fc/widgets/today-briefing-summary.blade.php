{{--
    Root element MUST be <x-filament-widgets::widget> so that Filament's grid.column
    component can apply col-span-full (from $this->columnSpan = 'full').
    Without this wrapper the widget renders in a single grid cell (~half width)
    while the tabs + table below it span the full row.
--}}
<x-filament-widgets::widget class="fi-wi-fc-briefing-summary col-span-full">
@php
    $data    = $this->getViewData();
    $session = $data['session'];
@endphp

{{-- ─────────────────────────────────────────────────────────────────────────── --}}
{{-- NO SESSION STATE                                                             --}}
{{-- ─────────────────────────────────────────────────────────────────────────── --}}
@if (! $session)
    <div class="flex flex-col gap-4 rounded-xl border border-dashed border-amber-300 bg-amber-50 p-5
                dark:border-amber-700 dark:bg-amber-950/30
                sm:flex-row sm:items-center sm:justify-between">

        <div class="flex items-start gap-4">
            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-amber-100 dark:bg-amber-900/40">
                <x-heroicon-o-clipboard-document-check class="h-6 w-6 text-amber-600 dark:text-amber-400" />
            </div>
            <div>
                <p class="text-base font-bold text-amber-900 dark:text-amber-100">Belum ada briefing hari ini</p>
                <p class="mt-1 text-sm text-amber-700 dark:text-amber-400">
                    Buat briefing harian untuk membuka gate operasional loading.
                </p>
            </div>
        </div>

        <a href="{{ $data['create_url'] }}"
           class="inline-flex shrink-0 items-center justify-center gap-2 rounded-lg bg-amber-600 px-5 py-2.5
                  text-sm font-semibold text-white shadow-sm transition-colors
                  hover:bg-amber-700 dark:bg-amber-700 dark:hover:bg-amber-600">
            <x-heroicon-m-plus class="h-4 w-4" />
            Mulai Briefing
        </a>
    </div>

{{-- ─────────────────────────────────────────────────────────────────────────── --}}
{{-- SESSION EXISTS                                                               --}}
{{-- ─────────────────────────────────────────────────────────────────────────── --}}
@else
    @php
        /** @var \App\Enums\MPCheckStatus|null $status */
        $status   = $data['status'];
        $isReady  = $data['is_ready'];
        $hadir    = (int) $data['hadir'];
        $target   = (int) $data['target'];
        $siap     = (int) $data['siap_kerja'];
        $recheck  = (int) $data['perlu_recheck'];
        $progress = $target > 0 ? min(100, (int) round(($hadir / $target) * 100)) : 0;

        // ── Status badge — task-specified color mapping ──────────────────────
        $statusMap = match ($status?->value) {
            'cleared' => [
                'label' => $status->label(),
                'bg'    => 'bg-green-100 dark:bg-green-900/30',
                'text'  => 'text-green-800 dark:text-green-300',
                'ring'  => 'ring-green-300 dark:ring-green-700',
                'dot'   => 'bg-green-500',
            ],
            'on_check' => [
                'label' => $status->label(),
                'bg'    => 'bg-amber-100 dark:bg-amber-900/30',
                'text'  => 'text-amber-800 dark:text-amber-300',
                'ring'  => 'ring-amber-300 dark:ring-amber-700',
                'dot'   => 'bg-amber-500',
            ],
            'waiting_action', 'failed' => [
                'label' => $status->label(),
                'bg'    => 'bg-red-100 dark:bg-red-900/30',
                'text'  => 'text-red-800 dark:text-red-300',
                'ring'  => 'ring-red-300 dark:ring-red-700',
                'dot'   => 'bg-red-500',
            ],
            default => [
                'label' => $status?->label() ?? 'Draft',
                'bg'    => 'bg-gray-100 dark:bg-gray-800',
                'text'  => 'text-gray-600 dark:text-gray-400',
                'ring'  => 'ring-gray-300 dark:ring-gray-700',
                'dot'   => 'bg-gray-400',
            ],
        };

        // ── Progress bar color ────────────────────────────────────────────────
        $barColor = $progress >= 100 ? 'bg-green-500'
            : ($progress >= 60  ? 'bg-amber-500' : 'bg-rose-500');

        // ── Gate loading status ───────────────────────────────────────────────
        if ($isReady) {
            $gate = ['label' => 'Terbuka',  'dot' => 'bg-green-500',
                     'bg'  => 'bg-green-50 dark:bg-green-900/20',
                     'text' => 'text-green-700 dark:text-green-300', 'pulse' => false];
        } else {
            $gate = ['label' => 'Tertutup', 'dot' => 'bg-red-500',
                     'bg'  => 'bg-red-50 dark:bg-red-900/20',
                     'text' => 'text-red-700 dark:text-red-300',   'pulse' => true];
        }

        // ── Card outer ring ───────────────────────────────────────────────────
        $isUrgent = in_array($status?->value, ['waiting_action', 'failed'], true);
        $cardRing = $isReady   ? 'ring-1 ring-green-200 dark:ring-green-800'
            : ($isUrgent ? 'ring-1 ring-red-200 dark:ring-red-800'
                         : 'ring-1 ring-gray-950/5 dark:ring-white/10');
    @endphp

    {{-- Card — full width of the widget grid cell --}}
    <div class="w-full overflow-hidden rounded-xl bg-white shadow-sm {{ $cardRing }} dark:bg-gray-900">

        {{-- ── Header ──────────────────────────────────────────────────────── --}}
        <div class="flex flex-col gap-2 border-b border-gray-100 px-4 py-3 dark:border-gray-800
                    sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-2.5">
                @if ($isReady)
                    <x-heroicon-m-check-badge class="h-5 w-5 shrink-0 text-green-500" />
                @else
                    <x-heroicon-o-clipboard-document-check class="h-5 w-5 shrink-0 text-gray-400" />
                @endif
                <span class="text-sm font-bold text-gray-900 dark:text-white">Briefing Hari Ini</span>
                <span class="text-xs text-gray-400 dark:text-gray-500">
                    &mdash; {{ today()->translatedFormat('l, d F Y') }}
                </span>
            </div>

            <div class="flex items-center gap-2">
                {{-- Status badge --}}
                <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-bold ring-1
                             {{ $statusMap['bg'] }} {{ $statusMap['text'] }} {{ $statusMap['ring'] }}">
                    <span class="h-2 w-2 rounded-full {{ $statusMap['dot'] }}"></span>
                    {{ $statusMap['label'] }}
                </span>

                <a href="{{ $data['view_url'] }}"
                   class="inline-flex items-center gap-1 text-xs font-medium text-primary-600 hover:text-primary-700
                          dark:text-primary-400 dark:hover:text-primary-300">
                    Detail
                    <x-heroicon-m-arrow-top-right-on-square class="h-3.5 w-3.5" />
                </a>
            </div>
        </div>

        {{--
            ── 4 Metric columns ─────────────────────────────────────────────────
            items-stretch ensures every cell fills the same row height.
            Each cell uses h-full + flex flex-col so content is pushed to the
            top and the full cell height is occupied regardless of content amount.
        --}}
        <div class="grid grid-cols-2 items-stretch divide-y divide-gray-100 dark:divide-gray-800
                    sm:grid-cols-4 sm:divide-x sm:divide-y-0">

            {{-- ① Target MP --}}
            <div class="flex h-full flex-col justify-between px-4 py-4">
                <p class="text-[11px] font-semibold uppercase tracking-widest text-gray-400 dark:text-gray-500">Target MP</p>
                <p class="mt-1 text-3xl font-extrabold tabular-nums text-gray-900 dark:text-white leading-none">
                    {{ $target }}
                </p>
                <p class="mt-1 text-xs text-gray-400">manpower</p>
            </div>

            {{-- ② Hadir + inline progress bar --}}
            <div class="flex h-full flex-col justify-between px-4 py-4">
                <p class="text-[11px] font-semibold uppercase tracking-widest text-gray-400 dark:text-gray-500">Hadir</p>
                <p class="mt-1 text-3xl font-extrabold tabular-nums text-gray-900 dark:text-white leading-none">
                    {{ $hadir }}
                    <span class="text-base font-normal text-gray-400">/ {{ $target }} MP</span>
                </p>
                {{-- Progress: bar + percentage on the same line --}}
                <div class="mt-2 flex items-center gap-2">
                    <div class="h-2.5 min-w-0 flex-1 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                        <div class="h-2.5 rounded-full {{ $barColor }} transition-all duration-500"
                             style="width: {{ max(2, $progress) }}%"></div>
                    </div>
                    <span class="shrink-0 text-xs font-semibold tabular-nums
                                 {{ $progress >= 100 ? 'text-green-600' : ($progress >= 60 ? 'text-amber-600' : 'text-rose-600') }}">
                        {{ $progress }}%
                    </span>
                </div>
            </div>

            {{-- ③ Siap Kerja --}}
            <div class="flex h-full flex-col justify-between px-4 py-4">
                <p class="text-[11px] font-semibold uppercase tracking-widest text-gray-400 dark:text-gray-500">Siap Kerja</p>
                <p class="mt-1 text-3xl font-extrabold tabular-nums leading-none
                           {{ $siap > 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-300 dark:text-gray-600' }}">
                    {{ $siap }}
                </p>
                <p class="mt-1 text-xs {{ $recheck > 0 ? 'font-medium text-amber-600 dark:text-amber-400' : 'text-gray-400' }}">
                    {{ $recheck > 0 ? $recheck . ' perlu recheck' : 'orang siap bertugas' }}
                </p>
            </div>

            {{-- ④ Gate Loading --}}
            <div class="flex h-full flex-col justify-between px-4 py-4">
                <p class="text-[11px] font-semibold uppercase tracking-widest text-gray-400 dark:text-gray-500">Gate Loading</p>
                <div class="mt-1.5 inline-flex w-fit items-center gap-2 rounded-lg px-3 py-1.5 {{ $gate['bg'] }}">
                    <span class="h-2.5 w-2.5 rounded-full {{ $gate['dot'] }} {{ $gate['pulse'] ? 'animate-pulse' : '' }}"></span>
                    <span class="text-sm font-bold {{ $gate['text'] }}">{{ $gate['label'] }}</span>
                </div>
                <p class="mt-1 text-xs text-gray-400">
                    {{ $isReady ? 'Operasional dapat berjalan' : 'MP Check belum selesai' }}
                </p>
            </div>
        </div>

    </div>{{-- /card --}}
@endif
</x-filament-widgets::widget>
