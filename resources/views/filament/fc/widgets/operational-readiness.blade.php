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
        'apdBelum' => $apdBelum,
        'apdTerisi' => $apdTerisi,
        'issues' => $issues,
        'mpPercent' => $mpPercent,
    ];

    $bannerClasses = match($state) {
        'ready'      => 'bg-emerald-600',
        'not_ready'  => 'bg-red-600',
        'no_session'  => 'bg-amber-500',
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

<div class="fi-section rounded-xl mb-4 overflow-hidden shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10">

    {{-- BANNER --}}
    <div class="{{ $bannerClasses }} px-5 py-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <x-dynamic-component :component="$bannerIcon" class="h-7 w-7 flex-shrink-0 text-white" />
                <div>
                    <div class="text-lg font-bold text-white">{{ $bannerText }}</div>
                    @if($d['session'])
                        <div class="text-sm text-white/80">{{ $d['session']->date?->format('d F Y') }} — {{ $d['session']->depot?->name ?? '-' }}</div>
                    @endif
                </div>
            </div>
            <div class="text-right">
                {{-- Status badge only adds information when the session is NOT ready;
                     when ready it always mirrors the banner, so it is omitted. --}}
                @if($state !== 'ready')
                    <x-filament::badge :color="$d['statusColor']" size="lg">
                        {{ $d['statusLabel'] }}
                    </x-filament::badge>
                @endif
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
            <div class="flex items-center gap-1.5">
                <x-heroicon-o-users class="h-3.5 w-3.5 flex-shrink-0 text-gray-400 dark:text-gray-500" />
                <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Kebutuhan MP</div>
            </div>
            <div class="mt-2 flex items-baseline gap-1">
                <span class="text-3xl font-bold text-gray-900 dark:text-white">{{ $d['hadir'] }}</span>
                <span class="text-lg text-gray-400">/</span>
                <span class="text-lg font-semibold text-gray-500">{{ $d['kebutuhan'] }}</span>
            </div>
            <div class="mt-3 h-1.5 w-full rounded-full bg-gray-200 dark:bg-gray-700">
                <div class="h-1.5 rounded-full {{ $mpBarColor }}" style="width: {{ $d['mpPercent'] }}%"></div>
            </div>
            <div class="mt-1.5 text-xs text-gray-500">{{ $d['mpPercent'] }}% terpenuhi</div>
        </div>

        {{-- Siap Kerja (FIT) --}}
        <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
            <div class="flex items-center gap-1.5">
                <x-heroicon-o-check-badge class="h-3.5 w-3.5 flex-shrink-0 text-gray-400 dark:text-gray-500" />
                <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Siap Kerja</div>
            </div>
            <div class="mt-2 text-3xl font-bold {{ $d['fit'] > 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-300 dark:text-gray-600' }}">{{ $d['fit'] }}</div>
            <div class="mt-1.5 text-xs {{ $d['unfit'] > 0 ? 'text-red-600 dark:text-red-400 font-medium' : 'text-gray-400' }}">
                @if($d['unfit'] > 0)
                    {{ $d['unfit'] }} tidak fit
                @else
                    Semua fit
                @endif
            </div>
        </div>

        {{-- Tidak Fit / Recheck --}}
        <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
            <div class="flex items-center gap-1.5">
                <x-heroicon-o-exclamation-circle class="h-3.5 w-3.5 flex-shrink-0 text-gray-400 dark:text-gray-500" />
                <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Tidak Fit / Recheck</div>
            </div>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="text-3xl font-bold {{ $d['unfit'] > 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-300 dark:text-gray-600' }}">{{ $d['unfit'] }}</span>
                <span class="text-gray-400">/</span>
                <span class="text-3xl font-bold {{ $d['recheck'] > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-gray-300 dark:text-gray-600' }}">{{ $d['recheck'] }}</span>
            </div>
            <div class="mt-1.5 text-xs text-gray-400">
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
            <div class="flex items-center gap-1.5">
                <x-heroicon-o-shield-check class="h-3.5 w-3.5 flex-shrink-0 text-gray-400 dark:text-gray-500" />
                <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Stok APD</div>
            </div>
            @if($d['apdKurang'] > 0)
                <div class="mt-2 text-3xl font-bold text-red-600 dark:text-red-400">{{ $d['apdKurang'] }}<span class="ml-1 text-base font-semibold">Kurang</span></div>
            @elseif($d['apdBelum'] > 0)
                <div class="mt-2 text-3xl font-bold text-amber-600 dark:text-amber-400">{{ $d['apdBelum'] }}<span class="ml-1 text-base font-semibold">Belum Diisi</span></div>
            @elseif($d['apdTotal'] > 0)
                <div class="mt-2 text-3xl font-bold text-emerald-600 dark:text-emerald-400">Cukup</div>
            @else
                <div class="mt-2 text-3xl font-bold text-gray-300 dark:text-gray-600">—</div>
            @endif
            @if($d['apdTotal'] > 0)
                <div class="mt-1.5 text-xs text-gray-400">{{ $d['apdTerisi'] }} / {{ $d['apdTotal'] }} item terisi</div>
            @else
                <div class="mt-1.5 text-xs text-gray-400">Belum dicek</div>
            @endif
        </div>

    </div>

    {{-- BLOCKING ISSUES --}}
    @if($state === 'not_ready' && count($d['issues']) > 0)
        <div class="mx-4 mb-4 rounded-lg border border-red-200 bg-red-50 p-3 dark:border-red-800/50 dark:bg-red-900/20">
            <div class="flex items-start gap-2.5">
                <x-heroicon-o-exclamation-circle class="h-4 w-4 flex-shrink-0 mt-0.5 text-red-600 dark:text-red-400" />
                <div>
                    <div class="text-sm font-semibold text-red-800 dark:text-red-300">Operasi Diblokir</div>
                    <ul class="mt-1.5 space-y-1">
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
    @elseif($state === 'ready' && count($d['issues']) > 0)
        {{-- Advisory only: these do NOT block operations (READY is unaffected). --}}
        <div class="mx-4 mb-4 rounded-lg border border-amber-200 bg-amber-50 p-3 dark:border-amber-800/50 dark:bg-amber-900/20">
            <div class="flex items-start gap-2.5">
                <x-heroicon-o-exclamation-triangle class="h-4 w-4 flex-shrink-0 mt-0.5 text-amber-600 dark:text-amber-400" />
                <div>
                    <div class="text-sm font-semibold text-amber-800 dark:text-amber-300">
                        Perlu Perhatian
                        <span class="ml-1 font-normal text-xs text-amber-700/80 dark:text-amber-400/80">— tidak memblokir operasional</span>
                    </div>
                    <ul class="mt-1.5 space-y-1">
                        @foreach($d['issues'] as $issue)
                            <li class="flex items-start gap-2 text-sm text-amber-700 dark:text-amber-400">
                                <span class="mt-1.5 h-1.5 w-1.5 flex-shrink-0 rounded-full bg-amber-500"></span>
                                {{ $issue }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @elseif($state === 'no_session')
        <div class="mx-4 mb-4 rounded-lg border border-amber-300 bg-amber-50 p-4 dark:border-amber-800/50 dark:bg-amber-900/20">
            <div class="flex items-start justify-between gap-3">
                <div class="flex items-start gap-3">
                    <x-heroicon-o-clock class="h-5 w-5 flex-shrink-0 mt-0.5 text-amber-600 dark:text-amber-400" />
                    <div>
                        <div class="text-sm font-semibold text-amber-800 dark:text-amber-300">Belum Ada Briefing Hari Ini</div>
                        <div class="mt-1 text-sm text-amber-700 dark:text-amber-400">Data kesiapan operasional akan muncul setelah briefing dimulai.</div>
                    </div>
                </div>
                @if(isset($createBriefingUrl))
                    <a href="{{ $createBriefingUrl }}"
                       class="flex-shrink-0 inline-flex items-center gap-1.5 rounded-lg bg-amber-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-amber-500 transition-colors focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-600">
                        <x-heroicon-m-plus class="h-4 w-4" />
                        Buat Briefing
                    </a>
                @endif
            </div>
        </div>
    @endif

</div>
