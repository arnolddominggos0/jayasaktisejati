<x-filament-panels::page>

    {{-- ══════════════════════════════════════════════════════════════════════
         CONTEXT HEADER — Branch / Depot + Urgency badge
    ══════════════════════════════════════════════════════════════════════ --}}
    <div class="mb-4">
        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">

                {{-- Nama Branch / Depot --}}
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary-50 dark:bg-primary-900/20">
                        <x-heroicon-m-building-office-2 class="h-6 w-6 text-primary-600 dark:text-primary-400" />
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Lingkup Operasional</p>
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="text-lg font-semibold text-gray-950 dark:text-white">
                                {{ $this->getBranchName() }}
                            </h2>
                            @if ($this->hasDepotContext())
                                <span class="text-gray-400 dark:text-gray-500">→</span>
                                <span class="text-base font-medium text-gray-700 dark:text-gray-300">
                                    {{ $this->getDepotName() }}
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Badges --}}
                <div class="flex flex-wrap items-center gap-2">
                    @php $opBadge = $this->getOperationalReadinessBadge(); @endphp
                    <x-filament::badge :color="$opBadge['color']" size="lg" :icon="$opBadge['icon']">
                        {{ $opBadge['label'] }}
                    </x-filament::badge>

                    @php $urgencyCount = $this->getUrgencyCount(); @endphp
                    @if ($urgencyCount > 0)
                        <x-filament::badge color="danger" size="sm" icon="heroicon-m-exclamation-triangle">
                            {{ $urgencyCount }} pengiriman butuh perhatian
                        </x-filament::badge>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════════
         BRIEFING BANNER
         Case A (belum ada): primary CTA → create, secondary → monitoring
         Case B (sudah ada): primary CTA → view detail, secondary → monitoring
    ══════════════════════════════════════════════════════════════════════ --}}
    @php $bs = $this->getTodayBriefingStatus(); @endphp

    @if (! $bs['has_briefing'])
        {{-- Belum ada sesi briefing — CTA langsung ke halaman create --}}
        <div class="mb-6 flex flex-col gap-3 rounded-xl border border-dashed border-amber-300 bg-amber-50 p-5
                    dark:border-amber-700 dark:bg-amber-950/30
                    sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-start gap-4">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-amber-100 dark:bg-amber-900/40">
                    <x-heroicon-o-clock class="h-6 w-6 text-amber-600 dark:text-amber-400" />
                </div>
                <div>
                    <p class="text-base font-bold text-amber-900 dark:text-amber-100">
                        Belum Ada Sesi Briefing Hari Ini
                    </p>
                    <p class="mt-0.5 text-sm text-amber-700 dark:text-amber-400">
                        {{ now()->translatedFormat('l, d F Y') }}
                    </p>
                </div>
            </div>
            <div class="flex shrink-0 flex-wrap items-center gap-2">
                {{-- Primary CTA — buat briefing sekarang --}}
                <a href="{{ $bs['create_url'] }}"
                   class="inline-flex items-center gap-2 rounded-lg bg-amber-600 px-5 py-2.5
                          text-sm font-semibold text-white shadow-sm transition-colors
                          hover:bg-amber-700 dark:bg-amber-700 dark:hover:bg-amber-600">
                    <x-heroicon-o-plus-circle class="h-4 w-4" />
                    Buat Briefing Hari Ini
                </a>
                {{-- Secondary CTA — read-only analytics --}}
                <a href="{{ $bs['monitoring_url'] }}"
                   class="inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2
                          text-sm font-semibold text-gray-700 shadow-sm ring-1 ring-gray-200
                          transition-colors hover:bg-gray-50
                          dark:bg-gray-800 dark:text-gray-200 dark:ring-gray-700 dark:hover:bg-gray-700">
                    <x-heroicon-o-presentation-chart-line class="h-4 w-4" />
                    Monitoring Operasional
                </a>
            </div>
        </div>

    @else
        {{-- Sudah briefing — tampilkan ringkasan + link ke detail & monitoring --}}
        @php
            $isReady  = $bs['is_ready'];
            $fitCount = $bs['fit_count'];
            $needMp   = $bs['need_mp'];
        @endphp
        <div class="mb-6 flex flex-col gap-3 rounded-xl border p-5
                    {{ $isReady
                        ? 'border-emerald-200 bg-emerald-50 dark:border-emerald-800 dark:bg-emerald-950/20'
                        : 'border-amber-200 bg-amber-50 dark:border-amber-800 dark:bg-amber-950/20' }}
                    sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-start gap-4">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl
                            {{ $isReady ? 'bg-emerald-100 dark:bg-emerald-900/40' : 'bg-amber-100 dark:bg-amber-900/40' }}">
                    @if ($isReady)
                        <x-heroicon-m-check-badge class="h-6 w-6 text-emerald-600 dark:text-emerald-400" />
                    @else
                        <x-heroicon-o-clock class="h-6 w-6 text-amber-600 dark:text-amber-400" />
                    @endif
                </div>
                <div>
                    <p class="text-base font-bold
                               {{ $isReady ? 'text-emerald-900 dark:text-emerald-100' : 'text-amber-900 dark:text-amber-100' }}">
                        {{ $isReady ? 'Sesi Briefing Ada — MP SIAP' : 'Sesi Briefing Ada — MP Belum SIAP' }}
                    </p>
                    <div class="mt-1 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm
                                {{ $isReady ? 'text-emerald-700 dark:text-emerald-400' : 'text-amber-700 dark:text-amber-400' }}">
                        <span>MP FIT <strong>{{ $fitCount }}/{{ $needMp }}</strong></span>
                        <span class="text-gray-300 dark:text-gray-600">|</span>
                        <span>{{ now()->translatedFormat('l, d F Y') }}</span>
                    </div>
                </div>
            </div>
            <div class="flex shrink-0 flex-wrap items-center gap-2">
                {{-- Primary CTA — lihat detail briefing hari ini --}}
                <a href="{{ $bs['view_url'] }}"
                   class="inline-flex items-center gap-2 rounded-lg px-5 py-2.5
                          text-sm font-semibold text-white shadow-sm transition-colors
                          {{ $isReady
                              ? 'bg-emerald-600 hover:bg-emerald-700 dark:bg-emerald-700 dark:hover:bg-emerald-600'
                              : 'bg-amber-600 hover:bg-amber-700 dark:bg-amber-700 dark:hover:bg-amber-600' }}">
                    <x-heroicon-m-document-text class="h-4 w-4" />
                    Lihat Briefing Hari Ini
                </a>
                {{-- Secondary CTA — read-only analytics --}}
                <a href="{{ $bs['monitoring_url'] }}"
                   class="inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2
                          text-sm font-semibold text-gray-700 shadow-sm ring-1 ring-gray-200
                          transition-colors hover:bg-gray-50
                          dark:bg-gray-800 dark:text-gray-200 dark:ring-gray-700 dark:hover:bg-gray-700">
                    <x-heroicon-o-presentation-chart-line class="h-4 w-4" />
                    Monitoring Operasional
                </a>
            </div>
        </div>
    @endif

    {{-- ══════════════════════════════════════════════════════════════════════
         STATUS HARI INI — 3 summary cards: Briefing · Container · Overall
    ══════════════════════════════════════════════════════════════════════ --}}
    @php $or = $this->getTodayOperationalReadiness(); @endphp
    <div class="mb-6">
        <div class="mb-2 flex items-center gap-2 px-1">
            <x-heroicon-o-signal class="h-4 w-4 text-gray-400 dark:text-gray-500" />
            <span class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">
                Status Hari Ini
            </span>
        </div>

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">

            {{-- Card 1 — Briefing Hari Ini --}}
            @php
                if (! $bs['has_briefing']) {
                    $bIconBg   = 'bg-gray-100 dark:bg-gray-800';
                    $bIconCol  = 'text-gray-400 dark:text-gray-500';
                    $bBadgeCls = 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400';
                    $bLabel    = 'Belum Ada';
                } elseif ($bs['is_ready']) {
                    $bIconBg   = 'bg-emerald-50 dark:bg-emerald-900/20';
                    $bIconCol  = 'text-emerald-600 dark:text-emerald-400';
                    $bBadgeCls = 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300';
                    $bLabel    = $bs['status_label'];
                } else {
                    $bIconBg   = 'bg-amber-50 dark:bg-amber-900/20';
                    $bIconCol  = 'text-amber-600 dark:text-amber-400';
                    $bBadgeCls = 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300';
                    $bLabel    = $bs['status_label'];
                }
            @endphp
            <div class="flex items-center gap-4 rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl {{ $bIconBg }}">
                    @if ($bs['has_briefing'])
                        <x-heroicon-o-clipboard-document-check class="h-6 w-6 {{ $bIconCol }}" />
                    @else
                        <x-heroicon-o-clock class="h-6 w-6 {{ $bIconCol }}" />
                    @endif
                </div>
                <div class="min-w-0">
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">
                        Briefing Hari Ini
                    </p>
                    <span class="mt-1 inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-bold {{ $bBadgeCls }}">
                        {{ $bLabel }}
                    </span>
                </div>
            </div>

            {{-- Card 2 — Container Readiness --}}
            @php
                if (! $or['has_container']) {
                    $cIconBg   = 'bg-gray-100 dark:bg-gray-800';
                    $cIconCol  = 'text-gray-400 dark:text-gray-500';
                    $cBadgeCls = 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400';
                    $cLabel    = 'Belum Diinput';
                } elseif ($or['container_ready']) {
                    $cIconBg   = 'bg-emerald-50 dark:bg-emerald-900/20';
                    $cIconCol  = 'text-emerald-600 dark:text-emerald-400';
                    $cBadgeCls = 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300';
                    $cLabel    = 'Ready';
                } else {
                    $cIconBg   = 'bg-rose-50 dark:bg-rose-900/20';
                    $cIconCol  = 'text-rose-600 dark:text-rose-400';
                    $cBadgeCls = 'bg-rose-100 text-rose-800 dark:bg-rose-900/30 dark:text-rose-300';
                    $cLabel    = 'Not Ready';
                }
            @endphp
            <div class="flex items-center gap-4 rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl {{ $cIconBg }}">
                    <x-heroicon-o-archive-box class="h-6 w-6 {{ $cIconCol }}" />
                </div>
                <div class="min-w-0">
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">
                        Container Readiness
                    </p>
                    <span class="mt-1 inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-bold {{ $cBadgeCls }}">
                        {{ $cLabel }}
                    </span>
                </div>
            </div>

            {{-- Card 3 — Operational Readiness (MP AND Container) --}}
            @php
                if ($or['overall'] === null) {
                    $oIconBg   = 'bg-gray-100 dark:bg-gray-800';
                    $oIconCol  = 'text-gray-400 dark:text-gray-500';
                    $oBadgeCls = 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400';
                    $oLabel    = 'Belum Ada Data';
                } elseif ($or['overall']) {
                    $oIconBg   = 'bg-emerald-50 dark:bg-emerald-900/20';
                    $oIconCol  = 'text-emerald-600 dark:text-emerald-400';
                    $oBadgeCls = 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300';
                    $oLabel    = 'Ready';
                } else {
                    $oIconBg   = 'bg-rose-50 dark:bg-rose-900/20';
                    $oIconCol  = 'text-rose-600 dark:text-rose-400';
                    $oBadgeCls = 'bg-rose-100 text-rose-800 dark:bg-rose-900/30 dark:text-rose-300';
                    $oLabel    = 'Not Ready';
                }
            @endphp
            <div class="flex items-center gap-4 rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl {{ $oIconBg }}">
                    <x-heroicon-o-check-badge class="h-6 w-6 {{ $oIconCol }}" />
                </div>
                <div class="min-w-0">
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">
                        Operational Readiness
                    </p>
                    <span class="mt-1 inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-bold {{ $oBadgeCls }}">
                        {{ $oLabel }}
                    </span>
                </div>
            </div>

        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════════
         SECTION 4B — Aktivitas Hari Ini
         4 KPI card: Masuk Yard · Ready Loading · Loading · Bermasalah
         Satu query agregasi — tidak ada model load.
    ══════════════════════════════════════════════════════════════════════ --}}
    @php $kpi = $this->getTodayActivityKpis(); @endphp
    <div class="mb-6">
        <div class="mb-2 flex items-center gap-2 px-1">
            <x-heroicon-o-bolt class="h-4 w-4 text-gray-400 dark:text-gray-500" />
            <span class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">
                Aktivitas Hari Ini
            </span>
        </div>

        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">

            {{-- A — Unit Masuk Yard --}}
            <div class="flex flex-col gap-1 rounded-xl bg-white px-5 py-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">
                    <x-heroicon-o-arrow-down-tray class="h-3.5 w-3.5" />
                    Masuk Yard
                </div>
                <p class="mt-1 text-3xl font-bold text-gray-900 dark:text-white">
                    {{ $kpi['handover_today'] }}
                </p>
                <p class="text-xs text-gray-400 dark:text-gray-500">unit handover hari ini</p>
            </div>

            {{-- B — Unit Ready Loading --}}
            <div class="flex flex-col gap-1 rounded-xl bg-white px-5 py-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wider text-emerald-500 dark:text-emerald-400">
                    <x-heroicon-o-check-circle class="h-3.5 w-3.5" />
                    Ready Loading
                </div>
                <p class="mt-1 text-3xl font-bold {{ $kpi['ready_loading'] > 0 ? 'text-emerald-700 dark:text-emerald-400' : 'text-gray-400 dark:text-gray-500' }}">
                    {{ $kpi['ready_loading'] }}
                </p>
                <p class="text-xs text-gray-400 dark:text-gray-500">unit siap dimuat</p>
            </div>

            {{-- C — Unit Loading Hari Ini --}}
            <div class="flex flex-col gap-1 rounded-xl bg-white px-5 py-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wider text-sky-500 dark:text-sky-400">
                    <x-heroicon-o-truck class="h-3.5 w-3.5" />
                    Loading
                </div>
                <p class="mt-1 text-3xl font-bold {{ $kpi['loading_today'] > 0 ? 'text-sky-700 dark:text-sky-400' : 'text-gray-400 dark:text-gray-500' }}">
                    {{ $kpi['loading_today'] }}
                </p>
                <p class="text-xs text-gray-400 dark:text-gray-500">unit keluar yard hari ini</p>
            </div>

            {{-- D — Unit Bermasalah Hari Ini --}}
            <div class="flex flex-col gap-1 rounded-xl bg-white px-5 py-4 shadow-sm ring-1 ring-gray-950/5
                        {{ $kpi['problematic_today'] > 0 ? 'ring-rose-200 dark:ring-rose-900/40' : '' }}
                        dark:bg-gray-900 dark:ring-white/10">
                <div class="flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wider
                            {{ $kpi['problematic_today'] > 0 ? 'text-rose-500 dark:text-rose-400' : 'text-gray-400 dark:text-gray-500' }}">
                    <x-heroicon-o-exclamation-circle class="h-3.5 w-3.5" />
                    Bermasalah
                </div>
                <p class="mt-1 text-3xl font-bold {{ $kpi['problematic_today'] > 0 ? 'text-rose-700 dark:text-rose-400' : 'text-gray-400 dark:text-gray-500' }}">
                    {{ $kpi['problematic_today'] }}
                </p>
                <p class="text-xs text-gray-400 dark:text-gray-500">unit return to PDC hari ini</p>
            </div>

        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════════
         SECTION 5 — Unit Butuh Tindakan
         Waiting Inspection + Bermasalah (return_to_pdc)
         Preview list max 5 baris per kategori + link ke Monitoring Operasional.
    ══════════════════════════════════════════════════════════════════════ --}}
    @php
        $needAction        = $this->getUnitsNeedingAction();
        $waitingPreview    = $this->getWaitingInspectionPreview();
        $bermasalahPreview = $this->getBermasalahPreview();
        $monitoringBase    = \App\Filament\FC\Pages\MpReadinessMonitoring::getUrl();
    @endphp
    @if ($needAction['total'] > 0)
    <div class="mb-6">
        <div class="mb-2 flex items-center gap-2 px-1">
            <x-heroicon-o-exclamation-triangle class="h-4 w-4 text-amber-400 dark:text-amber-500" />
            <span class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">
                Unit Butuh Tindakan
            </span>
            <span class="ml-1 rounded-full bg-rose-100 px-2 py-0.5 text-xs font-bold text-rose-700 dark:bg-rose-900/30 dark:text-rose-400">
                {{ $needAction['total'] }} unit
            </span>
        </div>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">

            {{-- ── Waiting Inspection ─────────────────────────────────────── --}}
            @if ($needAction['waiting'] > 0)
            <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                {{-- Header --}}
                <div class="flex items-center justify-between border-b border-gray-100 px-4 py-3 dark:border-gray-800">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-clock class="h-4 w-4 text-amber-500 dark:text-amber-400" />
                        <span class="text-sm font-semibold text-gray-700 dark:text-gray-200">Waiting Inspection</span>
                        <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-bold text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">
                            {{ $needAction['waiting'] }} unit
                        </span>
                    </div>
                    <a href="{{ $monitoringBase }}?tab=waiting_inspection"
                       class="text-xs font-medium text-primary-600 hover:underline dark:text-primary-400">
                        Lihat Semua →
                    </a>
                </div>

                {{-- Preview rows --}}
                <div class="divide-y divide-gray-50 dark:divide-gray-800/60">
                    @forelse ($waitingPreview as $unit)
                        <div class="flex items-center justify-between px-4 py-2.5">
                            <div class="flex items-center gap-3 min-w-0">
                                <span class="font-mono text-xs font-semibold text-gray-800 dark:text-gray-200 truncate">
                                    {{ $unit['sjkb_no'] }}
                                </span>
                                <span class="shrink-0 rounded bg-gray-100 px-1.5 py-0.5 text-xs text-gray-500 dark:bg-gray-800 dark:text-gray-400">
                                    {{ $unit['shipment_code'] }}
                                </span>
                            </div>
                            <span class="ml-3 shrink-0 text-xs font-semibold
                                         {{ $unit['waiting_days'] > 3 ? 'text-rose-600 dark:text-rose-400' : 'text-amber-600 dark:text-amber-400' }}">
                                {{ $unit['waiting_label'] }}
                            </span>
                        </div>
                    @empty
                        <div class="px-4 py-4 text-center text-xs text-gray-300 dark:text-gray-600">—</div>
                    @endforelse

                    @if ($needAction['waiting'] > 5)
                        <div class="px-4 py-2.5 text-xs text-gray-400 dark:text-gray-500">
                            + {{ $needAction['waiting'] - 5 }} unit lainnya —
                            <a href="{{ $monitoringBase }}?tab=waiting_inspection" class="text-primary-600 hover:underline dark:text-primary-400">
                                lihat semua
                            </a>
                        </div>
                    @endif
                </div>
            </div>
            @endif

            {{-- ── Unit Bermasalah ────────────────────────────────────────── --}}
            @if ($needAction['bermasalah'] > 0)
            <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                {{-- Header --}}
                <div class="flex items-center justify-between border-b border-gray-100 px-4 py-3 dark:border-gray-800">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-exclamation-triangle class="h-4 w-4 text-rose-500 dark:text-rose-400" />
                        <span class="text-sm font-semibold text-gray-700 dark:text-gray-200">Unit Bermasalah</span>
                        <span class="rounded-full bg-rose-100 px-2 py-0.5 text-xs font-bold text-rose-700 dark:bg-rose-900/30 dark:text-rose-400">
                            {{ $needAction['bermasalah'] }} unit
                        </span>
                    </div>
                    <a href="{{ $monitoringBase }}?tab=bermasalah"
                       class="text-xs font-medium text-primary-600 hover:underline dark:text-primary-400">
                        Lihat Semua →
                    </a>
                </div>

                {{-- Preview rows --}}
                <div class="divide-y divide-gray-50 dark:divide-gray-800/60">
                    @forelse ($bermasalahPreview as $unit)
                        <div class="flex items-center justify-between px-4 py-2.5">
                            <div class="flex items-center gap-3 min-w-0">
                                <span class="font-mono text-xs font-semibold text-gray-800 dark:text-gray-200 truncate">
                                    {{ $unit['sjkb_no'] }}
                                </span>
                                <span class="shrink-0 rounded bg-gray-100 px-1.5 py-0.5 text-xs text-gray-500 dark:bg-gray-800 dark:text-gray-400">
                                    {{ $unit['shipment_code'] }}
                                </span>
                            </div>
                            <span class="ml-3 shrink-0 rounded bg-rose-50 px-2 py-0.5 text-xs font-medium text-rose-700 dark:bg-rose-900/20 dark:text-rose-400 truncate max-w-[120px]"
                                  title="{{ $unit['remark'] }}">
                                {{ $unit['remark'] }}
                            </span>
                        </div>
                    @empty
                        <div class="px-4 py-4 text-center text-xs text-gray-300 dark:text-gray-600">—</div>
                    @endforelse

                    @if ($needAction['bermasalah'] > 5)
                        <div class="px-4 py-2.5 text-xs text-gray-400 dark:text-gray-500">
                            + {{ $needAction['bermasalah'] - 5 }} unit lainnya —
                            <a href="{{ $monitoringBase }}?tab=bermasalah" class="text-primary-600 hover:underline dark:text-primary-400">
                                lihat semua
                            </a>
                        </div>
                    @endif
                </div>
            </div>
            @endif

        </div>
    </div>
    @endif

</x-filament-panels::page>
