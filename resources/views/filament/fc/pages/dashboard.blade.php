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
         BRIEFING BANNER — read-only, navigasi ke Monitoring Operasional
         TIDAK ADA tombol create/edit di sini.
    ══════════════════════════════════════════════════════════════════════ --}}
    @php $bs = $this->getTodayBriefingStatus(); @endphp

    @if (! $bs['has_briefing'])
        {{-- Belum briefing — arahkan ke Monitoring Operasional --}}
        <div class="mb-6 flex flex-col gap-3 rounded-xl border border-dashed border-amber-300 bg-amber-50 p-5
                    dark:border-amber-700 dark:bg-amber-950/30
                    sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-start gap-4">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-amber-100 dark:bg-amber-900/40">
                    <x-heroicon-o-clock class="h-6 w-6 text-amber-600 dark:text-amber-400" />
                </div>
                <div>
                    <p class="text-base font-bold text-amber-900 dark:text-amber-100">
                        Belum Briefing Hari Ini
                    </p>
                    <p class="mt-0.5 text-sm text-amber-700 dark:text-amber-400">
                        {{ now()->translatedFormat('l, d F Y') }}
                    </p>
                </div>
            </div>
            <a href="{{ $bs['monitoring_url'] }}"
               class="inline-flex shrink-0 items-center gap-2 rounded-lg bg-amber-600 px-5 py-2.5
                      text-sm font-semibold text-white shadow-sm transition-colors
                      hover:bg-amber-700 dark:bg-amber-700 dark:hover:bg-amber-600">
                <x-heroicon-o-presentation-chart-line class="h-4 w-4" />
                Lanjutkan ke Monitoring Operasional →
            </a>
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
                        {{ $isReady ? 'Briefing Selesai — MP SIAP' : 'Briefing Ada — MP Belum SIAP' }}
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
                {{-- Detail briefing — view only, bukan edit --}}
                <a href="{{ $bs['view_url'] }}"
                   class="inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2
                          text-sm font-semibold text-gray-700 shadow-sm ring-1 ring-gray-200
                          transition-colors hover:bg-gray-50
                          dark:bg-gray-800 dark:text-gray-200 dark:ring-gray-700 dark:hover:bg-gray-700">
                    <x-heroicon-m-document-text class="h-4 w-4" />
                    Detail Briefing
                </a>
                <a href="{{ $bs['monitoring_url'] }}"
                   class="inline-flex items-center gap-2 rounded-lg px-5 py-2.5
                          text-sm font-semibold text-white shadow-sm transition-colors
                          {{ $isReady
                              ? 'bg-emerald-600 hover:bg-emerald-700 dark:bg-emerald-700 dark:hover:bg-emerald-600'
                              : 'bg-amber-600 hover:bg-amber-700 dark:bg-amber-700 dark:hover:bg-amber-600' }}">
                    <x-heroicon-o-presentation-chart-line class="h-4 w-4" />
                    Lihat Monitoring
                </a>
            </div>
        </div>
    @endif

    {{-- ══════════════════════════════════════════════════════════════════════
         SECTION 1 — Operasional Hari Ini (MP)
    ══════════════════════════════════════════════════════════════════════ --}}
    <div class="mb-6">
        <div class="mb-2 flex items-center gap-2 px-1">
            <x-heroicon-o-clipboard-document-check class="h-4 w-4 text-gray-400 dark:text-gray-500" />
            <span class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">
                Operasional Hari Ini
            </span>
        </div>
        <x-filament-widgets::widgets
            :widgets="[\App\Filament\FC\Widgets\DashboardOperationalWidget::class]"
            :columns="1"
        />
    </div>

    {{-- ══════════════════════════════════════════════════════════════════════
         SECTION 2 — Container Readiness Hari Ini
    ══════════════════════════════════════════════════════════════════════ --}}
    <div class="mb-6">
        <div class="mb-2 flex items-center gap-2 px-1">
            <x-heroicon-o-archive-box class="h-4 w-4 text-gray-400 dark:text-gray-500" />
            <span class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">
                Container Readiness Hari Ini
            </span>
        </div>
        <x-filament-widgets::widgets
            :widgets="[\App\Filament\FC\Widgets\ContainerSnapshotWidget::class]"
            :columns="1"
        />
    </div>

    {{-- ══════════════════════════════════════════════════════════════════════
         SECTION 3 — Operational Readiness Hari Ini (Gabungan MP + Container)
         Formula: READY = MP READY AND Container READY
    ══════════════════════════════════════════════════════════════════════ --}}
    @php $or = $this->getTodayOperationalReadiness(); @endphp
    <div class="mb-6">
        <div class="mb-2 flex items-center gap-2 px-1">
            <x-heroicon-o-check-badge class="h-4 w-4 text-gray-400 dark:text-gray-500" />
            <span class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">
                Operational Readiness Hari Ini
            </span>
        </div>

        <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="grid grid-cols-1 divide-y divide-gray-100 dark:divide-gray-800
                        sm:grid-cols-3 sm:divide-x sm:divide-y-0">

                {{-- MP Readiness --}}
                <div class="flex flex-col items-center justify-center gap-2 px-6 py-5">
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">MP Readiness</p>
                    @if (! $or['has_mp'])
                        <span class="text-sm font-medium text-gray-300 dark:text-gray-600">Belum Ada Data</span>
                    @elseif ($or['mp_ready'])
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-4 py-1.5
                                     text-sm font-bold text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300">
                            <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                            READY
                        </span>
                        @if ($or['mp_attend'] !== null)
                            <p class="text-xs text-gray-400">{{ $or['mp_attend'] }}/{{ $or['mp_need'] }} hadir</p>
                        @endif
                    @else
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-rose-100 px-4 py-1.5
                                     text-sm font-bold text-rose-800 dark:bg-rose-900/30 dark:text-rose-300">
                            <span class="h-2 w-2 animate-pulse rounded-full bg-rose-500"></span>
                            NOT READY
                        </span>
                        @if ($or['mp_attend'] !== null)
                            <p class="text-xs text-gray-400">{{ $or['mp_attend'] }}/{{ $or['mp_need'] }} hadir</p>
                        @endif
                    @endif
                </div>

                {{-- Container Readiness --}}
                <div class="flex flex-col items-center justify-center gap-2 px-6 py-5">
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Container Readiness</p>
                    @if (! $or['has_container'])
                        <span class="text-sm font-medium text-gray-300 dark:text-gray-600">Belum Ada Data</span>
                    @elseif ($or['container_ready'])
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-4 py-1.5
                                     text-sm font-bold text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300">
                            <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                            READY
                        </span>
                        @if ($or['container_avail'] !== null)
                            <p class="text-xs text-gray-400">{{ $or['container_avail'] }}/{{ $or['container_need'] }} tersedia</p>
                        @endif
                    @else
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-rose-100 px-4 py-1.5
                                     text-sm font-bold text-rose-800 dark:bg-rose-900/30 dark:text-rose-300">
                            <span class="h-2 w-2 animate-pulse rounded-full bg-rose-500"></span>
                            NOT READY
                        </span>
                        @if ($or['container_avail'] !== null)
                            <p class="text-xs text-gray-400">{{ $or['container_avail'] }}/{{ $or['container_need'] }} tersedia</p>
                        @endif
                    @endif
                </div>

                {{-- Overall --}}
                <div class="flex flex-col items-center justify-center gap-2 px-6 py-5
                            {{ $or['overall'] === true
                                ? 'bg-emerald-50 dark:bg-emerald-950/20'
                                : ($or['overall'] === false
                                    ? 'bg-rose-50 dark:bg-rose-950/20'
                                    : 'bg-gray-50 dark:bg-gray-800/40') }}">
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Overall</p>
                    @if ($or['overall'] === null)
                        <span class="text-sm font-medium text-gray-300 dark:text-gray-600">—</span>
                    @elseif ($or['overall'])
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-5 py-2
                                     text-base font-bold text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200">
                            <span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                            READY
                        </span>
                        <p class="text-xs font-medium text-emerald-600 dark:text-emerald-400">
                            MP ✓ &nbsp;|&nbsp; Container ✓
                        </p>
                    @else
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-rose-100 px-5 py-2
                                     text-base font-bold text-rose-800 dark:bg-rose-900/40 dark:text-rose-200">
                            <span class="h-2.5 w-2.5 animate-pulse rounded-full bg-rose-500"></span>
                            NOT READY
                        </span>
                        <p class="text-xs font-medium text-rose-600 dark:text-rose-400">
                            @if (! $or['has_mp'])         MP belum ada data
                            @elseif (! $or['mp_ready'])   MP ✗
                            @endif
                            @if ((! $or['has_mp'] || ! $or['mp_ready']) && (! $or['has_container'] || ! $or['container_ready']))
                                &nbsp;|&nbsp;
                            @endif
                            @if (! $or['has_container'])  Container belum ada data
                            @elseif (! $or['container_ready']) Container ✗
                            @endif
                        </p>
                    @endif
                </div>
            </div>
        </div>
    </div>
    {{-- ══════════════════════════════════════════════════════════════════════
         SECTION 4 — Shipment Aktif di Yard
         Status: LOADING > NG > READY > PARTIAL
    ══════════════════════════════════════════════════════════════════════ --}}
    @php $activeShipments = $this->getTodayActiveShipments(); @endphp
    <div class="mb-6">
        <div class="mb-2 flex items-center gap-2 px-1">
            <x-heroicon-o-truck class="h-4 w-4 text-gray-400 dark:text-gray-500" />
            <span class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">
                Shipment Aktif di Yard
            </span>
        </div>

        <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            @if (empty($activeShipments))
                <div class="p-8 text-center">
                    <x-heroicon-o-truck class="mx-auto h-10 w-10 text-gray-200 dark:text-gray-700" />
                    <p class="mt-3 text-sm font-medium text-gray-400 dark:text-gray-500">Belum ada shipment aktif di yard</p>
                    <p class="mt-1 text-xs text-gray-300 dark:text-gray-600">Shipment akan muncul setelah handover track diinput.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800/60">
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Shipment</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Voyage</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">Di Yard</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-emerald-500">Ready</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-amber-500">Waiting</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-rose-500">NG</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach ($activeShipments as $ship)
                                <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-800/40">
                                    <td class="px-4 py-3 font-semibold text-gray-900 dark:text-white">{{ $ship['code'] }}</td>
                                    <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ $ship['voyage'] ?? '—' }}</td>
                                    <td class="px-4 py-3 text-center font-medium text-gray-700 dark:text-gray-300">{{ $ship['in_yard'] }}</td>
                                    <td class="px-4 py-3 text-center font-semibold text-emerald-700 dark:text-emerald-400">{{ $ship['ready'] }}</td>
                                    <td class="px-4 py-3 text-center font-medium text-amber-600 dark:text-amber-400">{{ $ship['waiting'] }}</td>
                                    <td class="px-4 py-3 text-center font-semibold {{ $ship['ng'] > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-gray-300 dark:text-gray-600' }}">
                                        {{ $ship['ng'] > 0 ? $ship['ng'] : '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        @php
                                            $statusCls = match ($ship['color']) {
                                                'success' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300',
                                                'danger'  => 'bg-rose-100 text-rose-800 dark:bg-rose-900/30 dark:text-rose-300',
                                                'warning' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300',
                                                'info'    => 'bg-sky-100 text-sky-800 dark:bg-sky-900/30 dark:text-sky-300',
                                                default   => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
                                            };
                                        @endphp
                                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-bold {{ $statusCls }}">
                                            {{ $ship['status'] }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════════
         SECTION 5 — Unit Butuh Tindakan
         Waiting Inspection + Bermasalah (return_to_pdc)
    ══════════════════════════════════════════════════════════════════════ --}}
    @php $needAction = $this->getUnitsNeedingAction(); @endphp
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

        @php
            $monitoringBase = \App\Filament\FC\Pages\MpReadinessMonitoring::getUrl();
        @endphp
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            {{-- Waiting Inspection --}}
            <a href="{{ $monitoringBase }}?tab=waiting_inspection"
               class="group flex items-center gap-4 rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5
                      transition-shadow hover:shadow-md hover:ring-amber-200
                      dark:bg-gray-900 dark:ring-white/10 dark:hover:ring-amber-700">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-amber-50 dark:bg-amber-900/20">
                    <x-heroicon-o-clock class="h-6 w-6 text-amber-600 dark:text-amber-400" />
                </div>
                <div class="flex-1">
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">Waiting Inspection</p>
                    <p class="mt-0.5 text-3xl font-bold text-amber-600 dark:text-amber-400">{{ $needAction['waiting'] }}</p>
                    <p class="mt-0.5 text-xs text-gray-400 dark:text-gray-500">Unit dalam yard, belum diinspeksi</p>
                </div>
                <x-heroicon-m-arrow-right class="h-4 w-4 text-gray-300 transition-colors group-hover:text-amber-500 dark:text-gray-600" />
            </a>

            {{-- Bermasalah --}}
            <a href="{{ $monitoringBase }}?tab=bermasalah"
               class="group flex items-center gap-4 rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5
                      transition-shadow hover:shadow-md hover:ring-rose-200
                      dark:bg-gray-900 dark:ring-white/10 dark:hover:ring-rose-700">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-rose-50 dark:bg-rose-900/20">
                    <x-heroicon-o-exclamation-triangle class="h-6 w-6 text-rose-600 dark:text-rose-400" />
                </div>
                <div class="flex-1">
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">Unit Bermasalah</p>
                    <p class="mt-0.5 text-3xl font-bold text-rose-600 dark:text-rose-400">{{ $needAction['bermasalah'] }}</p>
                    <p class="mt-0.5 text-xs text-gray-400 dark:text-gray-500">Gate decision: return_to_pdc</p>
                </div>
                <x-heroicon-m-arrow-right class="h-4 w-4 text-gray-300 transition-colors group-hover:text-rose-500 dark:text-gray-600" />
            </a>
        </div>
    </div>
    @endif

</x-filament-panels::page>
