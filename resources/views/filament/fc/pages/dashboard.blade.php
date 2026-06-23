<x-filament-panels::page>

    {{-- ══════════════════════════════════════════════════════════════════════
         Hitung status briefing + kesiapan di awal untuk dipakai badge & section.
    ══════════════════════════════════════════════════════════════════════ --}}
    @php
        $bs        = $this->getTodayBriefingStatus();
        $kesiapan  = $this->getKesiapanOperasional();
        $perhatian = $this->getPerluPerhatian();

        // Badge ringkas status briefing — awareness saja, action ada di Tugas Operasional.
        if (! $bs['has_briefing']) {
            $briefingBadgeCls = 'bg-gray-100 text-gray-600 ring-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:ring-gray-700';
            $briefingBadgeIco = 'heroicon-m-clock';
            $briefingBadgeTxt = 'Belum Briefing';
        } elseif ($bs['is_ready']) {
            $briefingBadgeCls = 'bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-900/20 dark:text-emerald-400 dark:ring-emerald-800';
            $briefingBadgeIco = 'heroicon-m-check-badge';
            $briefingBadgeTxt = "Briefing Selesai · MP {$bs['fit_count']}/{$bs['need_mp']}";
        } else {
            $briefingBadgeCls = 'bg-amber-50 text-amber-700 ring-amber-200 dark:bg-amber-900/20 dark:text-amber-400 dark:ring-amber-800';
            $briefingBadgeIco = 'heroicon-m-exclamation-triangle';
            $briefingBadgeTxt = "Briefing · MP {$bs['fit_count']}/{$bs['need_mp']} Belum Siap";
        }
    @endphp

    {{-- ══════════════════════════════════════════════════════════════════════
         SECTION 1 — LINGKUP OPERASIONAL
         Konteks lokasi: Branch → Depot, dengan badge ringkas status briefing.
         Awareness saja — action Briefing/Container ada di Tugas Operasional.
    ══════════════════════════════════════════════════════════════════════ --}}
    <div class="mb-6">
        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
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

                {{-- Badge ringkas status briefing --}}
                <div class="shrink-0">
                    <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold ring-1 {{ $briefingBadgeCls }}">
                        <x-dynamic-component :component="$briefingBadgeIco" class="h-3.5 w-3.5" />
                        {{ $briefingBadgeTxt }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════════
         SECTION 2 — KESIAPAN OPERASIONAL HARI INI
         2 card: MP Readiness · Container Readiness.
    ══════════════════════════════════════════════════════════════════════ --}}
    <div class="mb-6">
        <div class="mb-2 flex items-center gap-2 px-1">
            <x-heroicon-o-signal class="h-4 w-4 text-gray-400 dark:text-gray-500" />
            <span class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">
                Kesiapan Operasional Hari Ini
            </span>
        </div>

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">

            {{-- Card 1 — MP Readiness --}}
            @php
                $mpFit  = $kesiapan['mp_fit'];
                $mpNeed = $kesiapan['mp_need'];
                if ($mpFit === null) {
                    $mpIconBg  = 'bg-gray-100 dark:bg-gray-800';
                    $mpIconCol = 'text-gray-400 dark:text-gray-500';
                    $mpPrimary = 'Menunggu Briefing';
                    $mpSub     = null;
                    $mpNumCls  = 'text-gray-400 dark:text-gray-500';
                    $mpPrimCls = 'text-base font-semibold';
                } else {
                    $mpReady   = $mpFit >= $mpNeed;
                    $mpIconBg  = $mpReady ? 'bg-emerald-50 dark:bg-emerald-900/20' : 'bg-amber-50 dark:bg-amber-900/20';
                    $mpIconCol = $mpReady ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400';
                    $mpPrimary = "{$mpFit} / {$mpNeed}";
                    $mpSub     = $mpReady ? 'MP Hadir — Siap' : 'MP Hadir — Belum Cukup';
                    $mpNumCls  = $mpReady ? 'text-emerald-700 dark:text-emerald-400' : 'text-amber-700 dark:text-amber-400';
                    $mpPrimCls = 'text-xl font-bold';
                }
            @endphp
            <div class="flex items-center gap-4 rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl {{ $mpIconBg }}">
                    <x-heroicon-o-users class="h-6 w-6 {{ $mpIconCol }}" />
                </div>
                <div class="min-w-0">
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">
                        MP Readiness
                    </p>
                    <p class="mt-0.5 {{ $mpPrimCls }} {{ $mpNumCls }}">{{ $mpPrimary }}</p>
                    @if ($mpSub)
                        <p class="text-xs text-gray-400 dark:text-gray-500">{{ $mpSub }}</p>
                    @endif
                </div>
            </div>

            {{-- Card 2 — Container Readiness --}}
            @php
                $cAvailKes = $kesiapan['container_available'];
                $cReady    = $kesiapan['container_ready'];
                if ($cAvailKes === null) {
                    $cIconBg   = 'bg-gray-100 dark:bg-gray-800';
                    $cIconCol  = 'text-gray-400 dark:text-gray-500';
                    $cPrimary  = 'Belum Diinput';
                    $cSub      = null;
                    $cNumCls   = 'text-gray-400 dark:text-gray-500';
                    $cPrimCls  = 'text-base font-semibold';
                } elseif ($cReady) {
                    $cIconBg   = 'bg-emerald-50 dark:bg-emerald-900/20';
                    $cIconCol  = 'text-emerald-600 dark:text-emerald-400';
                    $cPrimary  = "{$cAvailKes} Container";
                    $cSub      = 'Container Ready';
                    $cNumCls   = 'text-emerald-700 dark:text-emerald-400';
                    $cPrimCls  = 'text-xl font-bold';
                } else {
                    $cIconBg   = 'bg-rose-50 dark:bg-rose-900/20';
                    $cIconCol  = 'text-rose-600 dark:text-rose-400';
                    $cPrimary  = "{$cAvailKes} Container";
                    $cSub      = 'Container — Belum Cukup';
                    $cNumCls   = 'text-rose-700 dark:text-rose-400';
                    $cPrimCls  = 'text-xl font-bold';
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
                    <p class="mt-0.5 {{ $cPrimCls }} {{ $cNumCls }}">{{ $cPrimary }}</p>
                    @if ($cSub)
                        <p class="text-xs text-gray-400 dark:text-gray-500">{{ $cSub }}</p>
                    @endif
                </div>
            </div>

        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════════
         SECTION 3 — AKTIVITAS HARI INI
         4 KPI: Handover Hari Ini · Ready Loading · Loading Hari Ini · Bermasalah
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

            {{-- A — Handover Hari Ini --}}
            <div class="flex flex-col gap-1 rounded-xl bg-white px-5 py-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">
                    <x-heroicon-o-arrow-down-tray class="h-3.5 w-3.5" />
                    Handover Hari Ini
                </div>
                <p class="mt-1 text-3xl font-bold text-gray-900 dark:text-white">
                    {{ $kpi['handover_today'] }}
                </p>
                <p class="text-xs text-gray-400 dark:text-gray-500">unit masuk depot hari ini</p>
            </div>

            {{-- B — Ready Loading --}}
            <div class="flex flex-col gap-1 rounded-xl bg-white px-5 py-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wider text-emerald-500 dark:text-emerald-400">
                    <x-heroicon-o-check-circle class="h-3.5 w-3.5" />
                    Ready Loading
                </div>
                <p class="mt-1 text-3xl font-bold {{ $kpi['ready_loading'] > 0 ? 'text-emerald-700 dark:text-emerald-400' : 'text-gray-400 dark:text-gray-500' }}">
                    {{ $kpi['ready_loading'] }}
                </p>
                <p class="text-xs text-gray-400 dark:text-gray-500">unit lolos seluruh requirement</p>
            </div>

            {{-- C — Loading Hari Ini --}}
            <div class="flex flex-col gap-1 rounded-xl bg-white px-5 py-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wider text-sky-500 dark:text-sky-400">
                    <x-heroicon-o-truck class="h-3.5 w-3.5" />
                    Loading Hari Ini
                </div>
                <p class="mt-1 text-3xl font-bold {{ $kpi['loading_today'] > 0 ? 'text-sky-700 dark:text-sky-400' : 'text-gray-400 dark:text-gray-500' }}">
                    {{ $kpi['loading_today'] }}
                </p>
                <p class="text-xs text-gray-400 dark:text-gray-500">unit masuk proses loading hari ini</p>
            </div>

            {{-- D — Bermasalah --}}
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
         SECTION 4 — PERLU PERHATIAN
         Exception monitoring — selalu tampil, bukan conditional.
         FC harus tahu kondisi depot: merah jika ada masalah, hijau jika aman.
    ══════════════════════════════════════════════════════════════════════ --}}
    <div class="mb-6">
        <div class="mb-2 flex items-center gap-2 px-1">
            <x-heroicon-o-exclamation-triangle class="h-4 w-4 text-gray-400 dark:text-gray-500" />
            <span class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">
                Perlu Perhatian
            </span>
        </div>

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">

            {{-- Shipment Bermasalah --}}
            @if ($perhatian['bermasalah'] > 0)
            <div class="flex items-center gap-4 rounded-xl bg-white p-5 shadow-sm ring-1 ring-rose-200 dark:bg-gray-900 dark:ring-rose-900/50">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-rose-50 dark:bg-rose-900/20">
                    <x-heroicon-o-x-circle class="h-6 w-6 text-rose-600 dark:text-rose-400" />
                </div>
                <div class="min-w-0">
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">
                        Shipment Bermasalah
                    </p>
                    <p class="mt-0.5 text-xl font-bold text-rose-700 dark:text-rose-400">
                        {{ $perhatian['bermasalah'] }} Shipment
                    </p>
                    <p class="text-xs text-gray-400 dark:text-gray-500">Ada unit Return to PDC</p>
                </div>
            </div>
            @else
            <div class="flex items-center gap-4 rounded-xl bg-white p-5 shadow-sm ring-1 ring-emerald-100 dark:bg-gray-900 dark:ring-emerald-900/30">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-emerald-50 dark:bg-emerald-900/20">
                    <x-heroicon-o-check-circle class="h-6 w-6 text-emerald-500 dark:text-emerald-400" />
                </div>
                <div class="min-w-0">
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">
                        Shipment Bermasalah
                    </p>
                    <p class="mt-0.5 text-xl font-bold text-emerald-600 dark:text-emerald-400">0 Shipment</p>
                    <p class="text-xs text-emerald-500 dark:text-emerald-500">Tidak ada unit bermasalah</p>
                </div>
            </div>
            @endif

            {{-- Shipment Tertahan --}}
            @if ($perhatian['tertahan'] > 0)
            <div class="flex items-center gap-4 rounded-xl bg-white p-5 shadow-sm ring-1 ring-amber-200 dark:bg-gray-900 dark:ring-amber-900/50">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-amber-50 dark:bg-amber-900/20">
                    <x-heroicon-o-pause-circle class="h-6 w-6 text-amber-600 dark:text-amber-400" />
                </div>
                <div class="min-w-0">
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">
                        Shipment Tertahan
                    </p>
                    <p class="mt-0.5 text-xl font-bold text-amber-700 dark:text-amber-400">
                        {{ $perhatian['tertahan'] }} Shipment
                    </p>
                    <p class="text-xs text-gray-400 dark:text-gray-500">Track requirement belum selesai</p>
                </div>
            </div>
            @else
            <div class="flex items-center gap-4 rounded-xl bg-white p-5 shadow-sm ring-1 ring-emerald-100 dark:bg-gray-900 dark:ring-emerald-900/30">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-emerald-50 dark:bg-emerald-900/20">
                    <x-heroicon-o-check-circle class="h-6 w-6 text-emerald-500 dark:text-emerald-400" />
                </div>
                <div class="min-w-0">
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">
                        Shipment Tertahan
                    </p>
                    <p class="mt-0.5 text-xl font-bold text-emerald-600 dark:text-emerald-400">0 Shipment</p>
                    <p class="text-xs text-emerald-500 dark:text-emerald-500">Tidak ada shipment tertahan</p>
                </div>
            </div>
            @endif

        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════════
         SECTION 5 — UNIT AKTIF DI YARD
         Daftar unit yang masih dalam tanggung jawab depot asal.
         Track status: Pickup · Handover · Stuffing · DeliveryToPort · Stacking · UnitLoading
         Tidak termasuk OnShip dan seterusnya — unit sudah lepas dari depot.
         Diurutkan: latest_track_at DESC.
    ══════════════════════════════════════════════════════════════════════ --}}
    @php $yardUnits = $this->getActiveYardUnits(); @endphp
    <div class="mb-6">
        <div class="mb-2 flex items-center gap-2 px-1">
            <x-heroicon-o-cube-transparent class="h-4 w-4 text-sky-500 dark:text-sky-400" />
            <span class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">
                Unit Aktif di Yard
            </span>
            @if (count($yardUnits) > 0)
                <span class="ml-1 rounded-full bg-sky-100 px-2 py-0.5 text-xs font-bold text-sky-700 dark:bg-sky-900/30 dark:text-sky-400">
                    {{ count($yardUnits) }} unit
                </span>
            @endif
        </div>

        <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 bg-gray-50/70 dark:border-gray-800 dark:bg-gray-800/40">
                        <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">SJKB</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">Shipment</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">Unit</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">Status</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">Menunggu</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">Voyage</th>
                        <th class="px-4 py-2.5 text-right text-[11px] font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">Updated</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 dark:divide-gray-800/60">
                    @forelse ($yardUnits as $yu)
                        @php
                            // Badge + dot color per status — perjalanan dari abu-abu (awal) ke biru (loading).
                            $statusMeta = match($yu['status_key']) {
                                'pickup'           => ['badge' => 'bg-gray-100 text-gray-700 dark:bg-gray-700/60 dark:text-gray-300', 'dot' => 'bg-gray-400'],
                                'handover'         => ['badge' => 'bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400', 'dot' => 'bg-blue-500'],
                                'stuffing'         => ['badge' => 'bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400', 'dot' => 'bg-amber-500'],
                                'delivery_to_port' => ['badge' => 'bg-orange-50 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400', 'dot' => 'bg-orange-500'],
                                'stacking'         => ['badge' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300', 'dot' => 'bg-amber-600'],
                                'unit_loading'     => ['badge' => 'bg-sky-50 text-sky-700 dark:bg-sky-900/30 dark:text-sky-400', 'dot' => 'bg-sky-500'],
                                default            => ['badge' => 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400', 'dot' => 'bg-gray-300'],
                            };
                        @endphp
                        @php
                            $hasSjkb     = ! in_array($yu['sjkb_no'], ['—', '', null], true);
                            $shipmentUrl = \App\Filament\FC\Pages\OperationalTasks::getUrl() . '?tableSearch=' . urlencode($yu['shipment_code']);
                        @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40 transition-colors">
                            <td class="px-4 py-3">
                                @if ($hasSjkb)
                                    <span class="font-mono text-xs font-semibold text-gray-800 dark:text-gray-200">{{ $yu['sjkb_no'] }}</span>
                                @else
                                    <span class="inline-flex items-center gap-1 text-xs font-medium italic text-amber-600 dark:text-amber-400">
                                        <x-heroicon-m-pencil-square class="h-3 w-3" />
                                        Belum Input SJKB
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <a href="{{ $shipmentUrl }}"
                                   class="inline-block rounded bg-gray-100 px-1.5 py-0.5 font-mono text-xs font-medium text-primary-600 hover:bg-primary-50 hover:text-primary-700 dark:bg-gray-800 dark:text-primary-400 dark:hover:bg-primary-900/30 transition-colors">
                                    {{ $yu['shipment_code'] }}
                                </a>
                            </td>
                            <td class="px-4 py-3 text-xs font-medium text-gray-700 dark:text-gray-300">
                                {{ $yu['unit_label'] }}
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-[11px] font-semibold {{ $statusMeta['badge'] }}">
                                    <span class="h-1.5 w-1.5 rounded-full {{ $statusMeta['dot'] }}"></span>
                                    {{ $yu['status_label'] }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-500 dark:text-gray-400">
                                {{ $yu['next_requirement'] }}
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-500 dark:text-gray-400">
                                {{ $yu['voyage'] }}
                            </td>
                            <td class="px-4 py-3 text-right text-xs text-gray-400 dark:text-gray-500">
                                {{ $yu['updated_at'] }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center">
                                <div class="flex flex-col items-center gap-2">
                                    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-gray-50 dark:bg-gray-800">
                                        <x-heroicon-o-cube-transparent class="h-6 w-6 text-gray-300 dark:text-gray-600" />
                                    </div>
                                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                        Tidak ada unit aktif di yard saat ini
                                    </p>
                                    <p class="text-xs text-gray-400 dark:text-gray-500">
                                        Unit akan muncul di sini setelah penjemputan atau handover.
                                    </p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</x-filament-panels::page>
