@php
   $d = [
        'session' => $session,
        'state' => $state,
        'statusLabel' => $statusLabel,
        'statusColor' => $statusColor,
        'isReady' => $isReady,
        'kebutuhan' => $kebutuhan,
        'hadir' => $hadir,
        'fit' => $fit,
        'unfit' => $unfit,
        'recheck' => $recheck,
        'apdTotal' => $apdTotal,
        'apdKurang' => $apdKurang,
        'issues' => $issues,
        'mpPercent' => $mpPercent,
    ];

    $bannerClasses = match($state) {
        'ready'      => 'bg-gradient-to-r from-emerald-600 to-emerald-500',
        'not_ready'  => 'bg-gradient-to-r from-red-600 to-red-500',
        'no_session'  => 'bg-gradient-to-r from-amber-500 to-yellow-400',
    };

    $iconClasses = match($state) {
        'ready'      => 'text-white',
        'not_ready'  => 'text-white',
        'no_session'  => 'text-white',
    };

    $bannerIcon = match($state) {
        'ready'      => 'heroicon-o-check-circle',
        'not_ready'  => 'heroicon-o-exclamation-triangle',
        'no_session'  => 'heroicon-o-clock',
    };

    $bannerText = match($state) {
        'ready'      => 'SIAP OPERASIONAL',
        'not_ready'  => 'BELUM SIAP — Operasi Diblokir',
        'no_session'  => 'BELUM ADA BRIEFING HARI INI',
    };

    $mpBarColor = $d['mpPercent'] >= 100 ? 'bg-emerald-500' : ($d['mpPercent'] >= 50 ? 'bg-amber-400' : 'bg-red-500');
@endphp

<div class="fi-section rounded-xl mb-4 overflow-hidden shadow-lg ring-1 ring-gray-950/5 dark:ring-white/10">

    {{-- BANNER --}}
    <div class="{{ $bannerClasses }} px-5 py-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <x-heroicon-o-check-circle x-show="{{$state === 'ready'}}" class="h-8 w-8 text-white" />
                <x-heroicon-o-exclamation-triangle class="h-5 w-5 text-red-600 dark:text-red-400" />
                <x-heroicon-o-clock x-show="{{$state === 'no_session'}}" class="h-8 w-8 text-white" />
                <div>
                    <div class="text-lg font-bold text-white">{{ $bannerText }}</div>
                    @if($d['session'])
                        <div class="text-sm text-white/80">{{ $d['session']->date?->format('d F Y') }} — {{ $d['session']->depot?->name ?? '-' }}</div>
                    @endif
                </div>
            </div>
            <div class="text-right">
                <x-filament::badge :color="$d['statusColor']" size="lg">
                    {{ $d['statusLabel'] }}
                </x-filament::badge>
                @if($d['session']?->approved_at)
                    <div class="mt-1 text-xs text-white/70">Disetujui: {{ $d['session']->approved_at->format('H:i') }}</div>
                @endif
            </div>
        </div>
    </div>

    {{-- STAT CARDS --}}
    <div class="grid grid-cols-1 gap-3 p-4 sm:grid-cols-2 lg:grid-cols-4">

        {{-- MP Kebutuhan / Hadir --}}
        <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Kebutuhan MP</div>
                    <div class="mt-1 flex items-baseline gap-1">
                        <span class="text-3xl font-bold text-gray-900 dark:text-white">{{ $d['hadir'] }}</span>
                        <span class="text-lg text-gray-400">/</span>
                        <span class="text-lg font-semibold text-gray-500">{{ $d['kebutuhan'] }}</span>
                    </div>
                </div>
                <div class="flex h-10 w-10 items-center justify-center rounded-lg {{ $d['mpPercent'] >= 100 ? 'bg-emerald-100 dark:bg-emerald-900/30' : ($d['mpPercent'] >= 50 ? 'bg-amber-100 dark:bg-amber-900/30' : 'bg-red-100 dark:bg-red-900/30') }}">
                    <x-heroicon-o-users class="h-5 w-5 {{ $d['mpPercent'] >= 100 ? 'text-emerald-600 dark:text-emerald-400' : ($d['mpPercent'] >= 50 ? 'text-amber-600 dark:text-amber-400' : 'text-red-600 dark:text-red-400') }}" />
                </div>
            </div>
            <div class="mt-3 h-2 w-full rounded-full bg-gray-200 dark:bg-gray-700">
                <div class="h-2 rounded-full {{ $mpBarColor }}" style="width: {{ $d['mpPercent'] }}%"></div>
            </div>
            <div class="mt-1 text-xs text-gray-500">{{ $d['mpPercent'] }}% terpenuhi</div>
        </div>

        {{-- Siap Kerja (FIT) --}}
        <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Siap Kerja</div>
                    <div class="mt-1 text-3xl font-bold {{ $d['fit'] > 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-300 dark:text-gray-600' }}">{{ $d['fit'] }}</div>
                </div>
                <div class="flex h-10 w-10 items-center justify-center rounded-lg {{ $d['fit'] > 0 ? 'bg-emerald-100 dark:bg-emerald-900/30' : 'bg-gray-100 dark:bg-gray-700' }}">
                    <x-heroicon-o-check-badge class="h-5 w-5 {{ $d['fit'] > 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-400 dark:text-gray-500' }}" />
                </div>
            </div>
            <div class="mt-2 text-xs {{ $d['unfit'] > 0 ? 'text-red-600 dark:text-red-400 font-medium' : 'text-gray-400' }}">
                @if($d['unfit'] > 0)
                    {{ $d['unfit'] }} tidak fit
                @else
                    Semua fit
                @endif
            </div>
        </div>

        {{-- Tidak Fit / Recheck --}}
        <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Tidak Fit / Recheck</div>
                    <div class="mt-1 flex items-baseline gap-2">
                        <span class="text-2xl font-bold {{ $d['unfit'] > 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-300 dark:text-gray-600' }}">{{ $d['unfit'] }}</span>
                        <span class="text-gray-400">/</span>
                        <span class="text-2xl font-bold {{ $d['recheck'] > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-gray-300 dark:text-gray-600' }}">{{ $d['recheck'] }}</span>
                    </div>
                </div>
                <div class="flex h-10 w-10 items-center justify-center rounded-lg {{ ($d['unfit'] + $d['recheck']) > 0 ? 'bg-red-100 dark:bg-red-900/30' : 'bg-gray-100 dark:bg-gray-700' }}">
                    @if($d['unfit'] > 0)
                        <x-heroicon-o-x-circle class="h-5 w-5 text-red-600 dark:text-red-400" />
                    @elseif($d['recheck'] > 0)
                        <x-heroicon-o-clock class="h-5 w-5 text-amber-600 dark:text-amber-400" />
                    @else
                        <x-heroicon-o-check class="h-5 w-5 text-gray-400 dark:text-gray-500" />
                    @endif
                </div>
            </div>
            <div class="mt-2 text-xs text-gray-400">
                @if($d['unfit'] > 0 && $d['recheck'] > 0)
                    <span class="text-red-600 dark:text-red-400">{{ $d['unfit'] }} tidak fit</span> · <span class="text-amber-600 dark:text-amber-400">{{ $d['recheck'] }} recheck</span>
                @elseif($d['unfit'] > 0)
                    <span class="text-red-600 dark:text-red-400">{{ $d['unfit'] }} tidak fit</span>
                @elseif($d['recheck'] > 0)
                    <span class="text-amber-600 dark:text-amber-400">{{ $d['recheck'] }} menunggu recheck</span>
                @else
                    Tidak ada masalah
                @endif
            </div>
        </div>

        {{-- Stok APD --}}
        <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Stok APD</div>
                    @if($d['apdKurang'] > 0)
                        <div class="mt-1 text-2xl font-bold text-red-600 dark:text-red-400">{{ $d['apdKurang'] }} Kurang</div>
                    @elseif($d['apdTotal'] > 0)
                        <div class="mt-1 text-2xl font-bold text-emerald-600 dark:text-emerald-400">Cukup</div>
                    @else
                        <div class="mt-1 text-lg text-gray-400">Belum dicek</div>
                    @endif
                </div>
                <div class="flex h-10 w-10 items-center justify-center rounded-lg {{ $d['apdKurang'] > 0 ? 'bg-red-100 dark:bg-red-900/30' : ($d['apdTotal'] > 0 ? 'bg-emerald-100 dark:bg-emerald-900/30' : 'bg-gray-100 dark:bg-gray-700') }}">
                    @if($d['apdKurang'] > 0)
                        <x-heroicon-o-exclamation-circle class="h-5 w-5 text-red-600 dark:text-red-400" />
                    @elseif($d['apdTotal'] > 0)
                        <x-heroicon-o-shield-check class="h-5 w-5 text-emerald-600 dark:text-emerald-400" />
                    @else
                        <x-heroicon-o-question-mark-circle class="h-5 w-5 text-gray-400 dark:text-gray-500" />
                    @endif
                </div>
            </div>
            @if($d['apdTotal'] > 0)
                <div class="mt-2 text-xs text-gray-400">{{ $d['apdTotal'] }} item dicek</div>
            @endif
        </div>

    </div>

    {{-- BLOCKING ISSUES --}}
    @if($state === 'not_ready' && count($d['issues']) > 0)
        <div class="mx-4 mb-4 rounded-lg border-2 border-red-300 bg-red-50 p-4 dark:border-red-800/60 dark:bg-red-900/20">
            <div class="flex items-start gap-3">
                <x-heroicon-s-stop class="h-5 w-5 flex-shrink-0 mt-0.5 text-red-600 dark:text-red-400" />
                <div>
                    <div class="text-sm font-bold text-red-800 dark:text-red-300">Operasi Diblokir</div>
                    <ul class="mt-2 space-y-1.5">
                        @foreach($d['issues'] as $issue)
                            <li class="flex items-start gap-2 text-sm text-red-700 dark:text-red-400">
                                <span class="mt-1.5 h-1.5 w-1.5 flex-shrink-0 rounded-full bg-red-500"></span>
                                {{ $issue }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @elseif($state === 'no_session')
        <div class="mx-4 mb-4 rounded-lg border border-amber-300 bg-amber-50 p-4 dark:border-amber-800/50 dark:bg-amber-900/20">
            <div class="flex items-start gap-3">
                <x-heroicon-o-clock class="h-5 w-5 flex-shrink-0 mt-0.5 text-amber-600 dark:text-amber-400" />
                <div>
                    <div class="text-sm font-semibold text-amber-800 dark:text-amber-300">Belum Ada Briefing Hari Ini</div>
                    <div class="mt-1 text-sm text-amber-700 dark:text-amber-400">Data kesiapan operasional akan muncul setelah briefing dimulai.</div>
                </div>
            </div>
        </div>
    @endif

</div>
