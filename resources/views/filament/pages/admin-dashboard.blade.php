<x-filament-panels::page>

    @php
    $tamLeadTime  = $this->getTamLeadTimeSeries();
    $tamKpi       = $this->getTamKpiSummary();
    $tamPortStock = $this->getTamPortStockSummary();
    $tamMonthly   = $this->getTamMonthlyBreakdown();
    $insp         = $this->getInspeksiRingkasan();
    $ops          = $this->getOperationalNumbers();

    $tamTargets = $tamMonthly['targets'];
    $tamLabels  = array_column($tamMonthly['rows'], 'month');

    $dwVal    = $tamLeadTime['avg_days']['dwelling'] ?? 0;
    $saVal    = $tamLeadTime['avg_days']['sailing']  ?? 0;
    $doVal    = $tamLeadTime['avg_days']['dooring']  ?? 0;
    $dwTarget = $tamTargets['dwelling'];
    $saTarget = $tamTargets['sailing'];
    $doTarget = $tamTargets['dooring'];
    $dwOver   = $dwVal > $dwTarget && $dwVal > 0;
    $saOver   = $saVal > $saTarget && $saVal > 0;
    $doOver   = $doVal > $doTarget && $doVal > 0;
    $dwPct    = $dwTarget > 0 ? min(100, (int) round($dwVal / $dwTarget * 100)) : 0;
    $saPct    = $saTarget > 0 ? min(100, (int) round($saVal / $saTarget * 100)) : 0;
    $doPct    = $doTarget > 0 ? min(100, (int) round($doVal / $doTarget * 100)) : 0;

    $onTimeTotal  = (int) ($tamKpi['on_time'] ?? 0);
    $lateTotal    = (int) ($tamKpi['late']    ?? 0);
    $kpiTotal     = $onTimeTotal + $lateTotal;
    $onTimePct    = $kpiTotal > 0 ? (int) round($onTimeTotal / $kpiTotal * 100) : 0;

    $scopeUser   = auth_user();
    $scopeRole   = $scopeUser?->isSuperAdmin() ? 'Super Admin' : 'Office Admin';
    $scopeBranch = $scopeUser?->isSuperAdmin()
        ? 'Semua Cabang'
        : (\Illuminate\Support\Facades\DB::table('branches')->where('id', $scopeUser?->effectiveBranchId())->value('name') ?? 'Cabang');

    $tamBusinessRoute = \App\Supports\RouteCode::display(\App\Supports\RouteCode::default());

    $overPort  = (int) ($tamPortStock['over_three'] ?? 0);
    $ngCount   = (int) ($insp['ng'] ?? 0);
    $holdCount = (int) ($ops['shipment_hold'] ?? 0);
    @endphp

    {{-- Chart data: only kpi needed now --}}
    <div id="tam-chart-data"
         data-kpi='@json($tamKpi)'
         style="display:none"></div>

    <div class="jss-dash">

        {{-- ══════════════════════════════════════════════════════════════════
             PAGE HEADER
        ══════════════════════════════════════════════════════════════════ --}}
        <div class="jss-header flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div class="flex flex-col gap-3">
                <div class="jss-page-titles">
                    <h1 class="jss-h1">Dashboard</h1>
                    <p class="jss-subhead">Dashboard Operasional</p>
                </div>
                <div class="flex items-center flex-wrap gap-2">
                    <span class="jss-meta-badge bg-gray-100 text-gray-700 ring-gray-200">
                        <x-heroicon-m-user-circle class="h-3.5 w-3.5 text-gray-400" />
                        {{ $scopeRole }}
                    </span>
                    <span class="jss-meta-badge bg-amber-50 text-amber-700 ring-amber-200">
                        <x-heroicon-m-building-office class="h-3.5 w-3.5 text-amber-500" />
                        {{ $scopeBranch }}
                    </span>
                    <span class="jss-meta-badge bg-primary-50 text-primary-700 ring-primary-200">
                        <x-heroicon-m-globe-alt class="h-3.5 w-3.5 text-primary-500" />
                        {{ $tamBusinessRoute }} <span class="text-primary-400">· TAM</span>
                    </span>
                </div>
            </div>
            <div class="flex items-center gap-2.5 shrink-0">
                @if ($scopeUser?->isSuperAdmin())
                    <div class="text-xs text-gray-400 [&_.fi-fo-field-wrp]:mb-0 [&_.fi-input-wrp]:py-1 [&_.fi-input-wrp]:text-xs">
                        {{ $this->form }}
                    </div>
                @endif
                <span class="jss-meta-badge bg-gray-100 text-gray-500 ring-gray-200">
                    <x-heroicon-m-clock class="h-3.5 w-3.5 text-gray-400" />
                    {{ now()->format('H:i') }} WIB
                </span>
            </div>
        </div>

        {{-- ══════════════════════════════════════════════════════════════════
             ROW 1 — HERO KPI (4 cols)
        ══════════════════════════════════════════════════════════════════ --}}
        <section class="jss-section">
            <div class="jss-section-head">
                <h2 class="jss-section-title">Kondisi Operasi Saat Ini</h2>
                <p class="jss-section-desc">Snapshot aktivitas operasional hari ini</p>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">

                {{-- Unit Aktif --}}
                <div class="jss-card group p-5 flex flex-col">
                    <div class="flex items-center justify-between mb-3">
                        <div class="jss-iconbox bg-blue-50 ring-blue-100/60">
                            <x-heroicon-o-cube class="w-5 h-5 text-blue-600" />
                        </div>
                        <span class="jss-pill bg-blue-50 text-blue-600">Aktif</span>
                    </div>
                    <p class="jss-kpi-num text-blue-600">{{ number_format($ops['unit_aktif']) }}</p>
                    <p class="jss-kpi-label">Unit Aktif</p>
                    <p class="jss-kpi-desc">Total unit dalam proses</p>
                    <div class="jss-card-foot">
                        <a href="{{ \App\Filament\Resources\ShipmentResource::getUrl('index') }}" class="jss-link">
                            Lihat Detail <x-heroicon-m-arrow-right class="w-3.5 h-3.5" />
                        </a>
                    </div>
                </div>

                {{-- Unit Belum Assign Voyage --}}
                <div class="jss-card group p-5 flex flex-col {{ $ops['belum_voyage'] > 0 ? 'jss-card-warn' : '' }}">
                    <div class="flex items-center justify-between mb-3">
                        <div class="jss-iconbox {{ $ops['belum_voyage'] > 0 ? 'bg-amber-50 ring-amber-100/60' : 'bg-gray-50 ring-gray-100/60' }}">
                            <x-heroicon-o-rocket-launch class="w-5 h-5 {{ $ops['belum_voyage'] > 0 ? 'text-amber-600' : 'text-gray-400' }}" />
                        </div>
                        @if ($ops['belum_voyage'] > 0)
                            <span class="jss-pill bg-amber-50 text-amber-600">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span> Warning
                            </span>
                        @endif
                    </div>
                    <p class="jss-kpi-num {{ $ops['belum_voyage'] > 0 ? 'text-amber-600' : 'text-gray-300' }}">{{ $ops['belum_voyage'] }}</p>
                    <p class="jss-kpi-label">Belum Assign Voyage</p>
                    <p class="jss-kpi-desc">Unit yang belum diassign ke voyage</p>
                    <div class="jss-card-foot">
                        <a href="{{ \App\Filament\Resources\ShipmentResource::getUrl('index') }}" class="jss-link">
                            Lihat Detail <x-heroicon-m-arrow-right class="w-3.5 h-3.5" />
                        </a>
                    </div>
                </div>

                {{-- Unit Menunggu Pickup --}}
                <div class="jss-card group p-5 flex flex-col">
                    <div class="flex items-center justify-between mb-3">
                        <div class="jss-iconbox bg-purple-50 ring-purple-100/60">
                            <x-heroicon-o-truck class="w-5 h-5 text-purple-600" />
                        </div>
                        <span class="jss-pill bg-purple-50 text-purple-600">Pickup</span>
                    </div>
                    <p class="jss-kpi-num text-purple-600">{{ $ops['menunggu_pickup'] }}</p>
                    <p class="jss-kpi-label">Menunggu Pickup</p>
                    <p class="jss-kpi-desc">Siap diambil transporter</p>
                    <div class="jss-card-foot">
                        <a href="{{ \App\Filament\Resources\ShipmentResource::getUrl('index') }}" class="jss-link">
                            Lihat Detail <x-heroicon-m-arrow-right class="w-3.5 h-3.5" />
                        </a>
                    </div>
                </div>

                {{-- Unit Late --}}
                <div class="jss-card group p-5 flex flex-col {{ $lateTotal > 0 ? 'jss-card-danger' : '' }}">
                    <div class="flex items-center justify-between mb-3">
                        <div class="jss-iconbox {{ $lateTotal > 0 ? 'bg-red-50 ring-red-100/60' : 'bg-gray-50 ring-gray-100/60' }}">
                            <x-heroicon-o-exclamation-triangle class="w-5 h-5 {{ $lateTotal > 0 ? 'text-red-600' : 'text-gray-400' }}" />
                        </div>
                        @if ($lateTotal > 0)
                            <span class="jss-pill bg-red-50 text-red-600">
                                <span class="flex h-2 w-2 relative">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-2 w-2 bg-red-500"></span>
                                </span>
                                Over
                            </span>
                        @endif
                    </div>
                    <p class="jss-kpi-num {{ $lateTotal > 0 ? 'text-red-600' : 'text-gray-300' }}">{{ $lateTotal }}</p>
                    <p class="jss-kpi-label">Unit Terlambat</p>
                    <p class="jss-kpi-desc">Melewati total target lead time</p>
                    <div class="jss-card-foot">
                        <a href="{{ \App\Filament\Resources\ShipmentTrackingResource::getUrl('index') }}" class="jss-link">
                            Lihat Detail <x-heroicon-m-arrow-right class="w-3.5 h-3.5" />
                        </a>
                    </div>
                </div>

            </div>
        </section>

        {{-- ══════════════════════════════════════════════════════════════════
             ROW 2 — Alert | Port | Performa (3 cols)
        ══════════════════════════════════════════════════════════════════ --}}
        <section class="jss-section">
            <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">

                {{-- ══ COL 1: ALERT OPERASIONAL ══ --}}
                <div class="jss-card p-5 flex flex-col">
                    <div class="jss-card-head">
                        <h3 class="jss-card-title">Alert Operasional</h3>
                        <p class="jss-card-sub">Perlu perhatian segera</p>
                    </div>

                    <div class="flex flex-col flex-1 gap-1">

                        <div class="jss-alert-row {{ $overPort > 0 ? 'is-danger' : 'is-muted' }}">
                            <div class="jss-alert-row-icon {{ $overPort > 0 ? 'bg-red-100' : 'bg-gray-100' }}">
                                <x-heroicon-o-exclamation-triangle class="w-4 h-4 {{ $overPort > 0 ? 'text-red-600' : 'text-gray-400' }}" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="jss-alert-row-title {{ $overPort > 0 ? 'text-red-700' : 'text-gray-600' }}">Unit Over 3 Hari</p>
                                <p class="jss-alert-row-desc {{ $overPort > 0 ? 'text-red-500' : 'text-gray-400' }}">Berisiko demurrage</p>
                            </div>
                            <span class="jss-alert-row-num {{ $overPort > 0 ? 'text-red-600' : 'text-gray-300' }} tabular-nums">{{ $overPort }}</span>
                            <x-heroicon-m-chevron-right class="w-5 h-5 {{ $overPort > 0 ? 'text-red-400' : 'text-gray-300' }} shrink-0" />
                        </div>

                        <div class="jss-alert-row {{ $ngCount > 0 ? 'is-warning' : 'is-muted' }}">
                            <div class="jss-alert-row-icon {{ $ngCount > 0 ? 'bg-amber-100' : 'bg-gray-100' }}">
                                <x-heroicon-o-magnifying-glass-circle class="w-4 h-4 {{ $ngCount > 0 ? 'text-amber-600' : 'text-gray-400' }}" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="jss-alert-row-title {{ $ngCount > 0 ? 'text-amber-700' : 'text-gray-600' }}">Temuan NG</p>
                                <p class="jss-alert-row-desc {{ $ngCount > 0 ? 'text-amber-500' : 'text-gray-400' }}">Perlu tindakan lanjutan</p>
                            </div>
                            <span class="jss-alert-row-num {{ $ngCount > 0 ? 'text-amber-600' : 'text-gray-300' }} tabular-nums">{{ $ngCount }}</span>
                            <x-heroicon-m-chevron-right class="w-5 h-5 {{ $ngCount > 0 ? 'text-amber-400' : 'text-gray-300' }} shrink-0" />
                        </div>

                        <div class="jss-alert-row jss-alert-row-last {{ $holdCount > 0 ? 'is-info' : 'is-muted' }}">
                            <div class="jss-alert-row-icon {{ $holdCount > 0 ? 'bg-purple-100' : 'bg-gray-100' }}">
                                <x-heroicon-o-pause-circle class="w-4 h-4 {{ $holdCount > 0 ? 'text-purple-600' : 'text-gray-400' }}" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="jss-alert-row-title {{ $holdCount > 0 ? 'text-purple-700' : 'text-gray-600' }}">Shipment Hold</p>
                                <p class="jss-alert-row-desc">Menunggu release</p>
                            </div>
                            <span class="jss-alert-row-num {{ $holdCount > 0 ? 'text-purple-600' : 'text-gray-300' }} tabular-nums">{{ $holdCount }}</span>
                            <x-heroicon-m-chevron-right class="w-5 h-5 {{ $holdCount > 0 ? 'text-purple-400' : 'text-gray-300' }} shrink-0" />
                        </div>
                    </div>

                    <div class="jss-card-foot">
                        <a href="{{ \App\Filament\Resources\ShipmentTrackingResource::getUrl('index') }}" class="jss-link">
                            Lihat Semua Alert <x-heroicon-m-arrow-right class="w-3.5 h-3.5" />
                        </a>
                    </div>
                </div>

                {{-- ══ COL 2: RINGKASAN PORT ══ --}}
                <div class="jss-card p-5 flex flex-col">
                    <div class="jss-card-head">
                        <h3 class="jss-card-title">Ringkasan Port</h3>
                        <p class="jss-card-sub">Kondisi unit di port</p>
                    </div>

                    <div class="grid grid-cols-3 gap-2 flex-1 items-center">
                        <div class="jss-metric text-center">
                            <div class="jss-iconbox bg-blue-50 ring-blue-100/60 mx-auto mb-1">
                                <x-heroicon-o-map-pin class="w-5 h-5 text-blue-600" />
                            </div>
                            <p class="jss-metric-num">{{ $tamPortStock['total'] ?? 0 }}</p>
                            <p class="jss-metric-label">Unit di Port</p>
                        </div>
                        <div class="jss-metric text-center sm:border-x sm:border-gray-100">
                            <div class="jss-iconbox bg-orange-50 ring-orange-100/60 mx-auto mb-1">
                                <x-heroicon-o-clock class="w-5 h-5 text-orange-500" />
                            </div>
                            <p class="jss-metric-num">{{ number_format($tamPortStock['avg_age'] ?? 0, 1) }}</p>
                            <p class="jss-metric-label">Rata-rata Hari</p>
                        </div>
                        <div class="jss-metric text-center">
                            <div class="jss-iconbox {{ $overPort > 0 ? 'bg-red-50 ring-red-100/60' : 'bg-gray-50 ring-gray-100/60' }} mx-auto mb-1">
                                <x-heroicon-o-exclamation-triangle class="w-5 h-5 {{ $overPort > 0 ? 'text-red-500' : 'text-gray-400' }}" />
                            </div>
                            <p class="jss-metric-num {{ $overPort > 0 ? 'text-red-600' : 'text-gray-300' }}">{{ $overPort }}</p>
                            <p class="jss-metric-label">Over 3 Hari</p>
                        </div>
                    </div>

                    @if (($tamPortStock['total'] ?? 0) > 0 && $overPort > 0)
                        <div class="jss-banner-danger mt-3">
                            <x-heroicon-o-exclamation-circle class="w-4 h-4 shrink-0" />
                            <span>{{ $overPort }} dari {{ $tamPortStock['total'] }} unit berisiko demurrage</span>
                        </div>
                    @endif

                    <div class="jss-card-foot">
                        <a href="{{ \App\Filament\Resources\ShipmentTrackingResource::getUrl('index') }}" class="jss-link">
                            Lihat Port Stock <x-heroicon-m-arrow-right class="w-3.5 h-3.5" />
                        </a>
                    </div>
                </div>

                {{-- ══ COL 3: PERFORMA BULAN INI ══ --}}
                <div class="jss-card p-5 flex flex-col">
                    <div class="jss-card-head">
                        <h3 class="jss-card-title">Performa Bulan Ini</h3>
                        <p class="jss-card-sub">KPI pengiriman bulan berjalan</p>
                    </div>

                    <div class="flex items-center gap-3 flex-1">
                        <div class="relative shrink-0 w-44 h-44">
                            <canvas id="perfChart"></canvas>
                            <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                                <span id="perf-center-pct" class="text-3xl font-extrabold text-gray-900 tabular-nums leading-none">{{ $onTimePct }}%</span>
                                <span class="text-[10px] font-semibold tracking-wide text-gray-400 mt-1">On Time</span>
                            </div>
                        </div>
                        <div class="flex flex-col gap-3 flex-1">
                            <div class="jss-perf-item">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500 shrink-0"></span>
                                    <span class="text-[11px] font-medium text-gray-400">On Time</span>
                                </div>
                                <p class="text-xl font-bold text-gray-900 tabular-nums leading-none">{{ number_format($onTimeTotal) }}<span class="text-[11px] font-normal text-gray-400 ml-1">Unit</span></p>
                            </div>
                            <div class="jss-perf-item">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="w-2 h-2 rounded-full bg-red-400 shrink-0"></span>
                                    <span class="text-[11px] font-medium text-gray-400">Late</span>
                                </div>
                                <p class="text-xl font-bold tabular-nums leading-none {{ $lateTotal > 0 ? 'text-red-600' : 'text-gray-300' }}">{{ $lateTotal }}<span class="text-[11px] font-normal text-gray-400 ml-1">Unit</span></p>
                            </div>
                        </div>
                    </div>

                    <div class="jss-card-foot">
                        <a href="{{ \App\Filament\Resources\ShipmentHistoryResource::getUrl('index') }}" class="jss-link">
                            Lihat Shipment Late <x-heroicon-m-arrow-right class="w-3.5 h-3.5" />
                        </a>
                    </div>
                </div>

            </div>
        </section>

        {{-- ══════════════════════════════════════════════════════════════════
             ROW 3 — Lead Time (7/12) | Pemeriksaan (5/12)
        ══════════════════════════════════════════════════════════════════ --}}
        <section class="jss-section">
            <div class="grid grid-cols-1 gap-4 lg:grid-cols-12">

                {{-- ══ COL LEFT: KESEHATAN LEAD TIME ══ --}}
                <div class="lg:col-span-7 jss-card p-5 flex flex-col">
                    <div class="jss-card-head">
                        <h3 class="jss-card-title">Kesehatan Lead Time</h3>
                        <p class="jss-card-sub">Rata-rata per tahap vs target TAM (Bulan Ini)</p>
                    </div>

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3 flex-1">

                        {{-- Dwelling --}}
                        <div class="jss-lt-card {{ $dwOver ? 'is-over' : 'is-ok' }}">
                            <div class="flex items-center gap-2 mb-1.5">
                                <div class="jss-lt-iconbox {{ $dwOver ? 'bg-red-100' : 'bg-blue-100' }}">
                                    <x-heroicon-o-clock class="w-4 h-4 {{ $dwOver ? 'text-red-500' : 'text-blue-600' }}" />
                                </div>
                                <span class="jss-lt-label">Dwelling</span>
                            </div>
                            <div class="flex items-baseline gap-1 mb-0.5">
                                <span class="jss-lt-num {{ $dwOver ? 'text-red-600' : 'text-gray-900' }}">{{ $dwVal }}</span>
                                <span class="text-xs text-gray-400 font-medium">Hari</span>
                            </div>
                            <p class="jss-lt-target">Target: {{ $dwTarget }} hari</p>
                            <div class="jss-progress mt-1.5">
                                <div class="jss-progress-fill {{ $dwOver ? 'bg-red-500' : 'bg-emerald-500' }}" style="width: {{ $dwPct }}%"></div>
                            </div>
                            <div class="mt-1.5">
                                @if ($dwVal > 0)
                                    <span class="jss-badge {{ $dwOver ? 'jss-badge-danger' : 'jss-badge-success' }}">
                                        {{ $dwOver ? 'OVER' : 'OK' }}
                                    </span>
                                @else
                                    <span class="text-xs text-gray-400 font-medium">Belum ada data</span>
                                @endif
                            </div>
                        </div>

                        {{-- Sailing --}}
                        <div class="jss-lt-card {{ $saOver ? 'is-over' : 'is-ok' }}">
                            <div class="flex items-center gap-2 mb-1.5">
                                <div class="jss-lt-iconbox {{ $saOver ? 'bg-red-100' : 'bg-indigo-100' }}">
                                    <x-heroicon-o-lifebuoy class="w-4 h-4 {{ $saOver ? 'text-red-500' : 'text-indigo-600' }}" />
                                </div>
                                <span class="jss-lt-label">Sailing</span>
                            </div>
                            <div class="flex items-baseline gap-1 mb-0.5">
                                <span class="jss-lt-num {{ $saOver ? 'text-red-600' : 'text-gray-900' }}">{{ $saVal }}</span>
                                <span class="text-xs text-gray-400 font-medium">Hari</span>
                            </div>
                            <p class="jss-lt-target">Target: {{ $saTarget }} hari</p>
                            <div class="jss-progress mt-1.5">
                                <div class="jss-progress-fill {{ $saOver ? 'bg-red-500' : 'bg-emerald-500' }}" style="width: {{ $saPct }}%"></div>
                            </div>
                            <div class="mt-1.5">
                                @if ($saVal > 0)
                                    <span class="jss-badge {{ $saOver ? 'jss-badge-danger' : 'jss-badge-success' }}">
                                        {{ $saOver ? 'OVER' : 'OK' }}
                                    </span>
                                @else
                                    <span class="text-xs text-gray-400 font-medium">Belum ada data</span>
                                @endif
                            </div>
                        </div>

                        {{-- Dooring --}}
                        <div class="jss-lt-card {{ $doOver ? 'is-over' : 'is-ok' }}">
                            <div class="flex items-center gap-2 mb-1.5">
                                <div class="jss-lt-iconbox {{ $doOver ? 'bg-red-100' : 'bg-emerald-100' }}">
                                    <x-heroicon-o-truck class="w-4 h-4 {{ $doOver ? 'text-red-500' : 'text-emerald-600' }}" />
                                </div>
                                <span class="jss-lt-label">Dooring</span>
                            </div>
                            <div class="flex items-baseline gap-1 mb-0.5">
                                <span class="jss-lt-num {{ $doOver ? 'text-red-600' : 'text-gray-900' }}">{{ $doVal }}</span>
                                <span class="text-xs text-gray-400 font-medium">Hari</span>
                            </div>
                            <p class="jss-lt-target">Target: {{ $doTarget }} hari</p>
                            <div class="jss-progress mt-1.5">
                                <div class="jss-progress-fill {{ $doOver ? 'bg-red-500' : 'bg-emerald-500' }}" style="width: {{ $doPct }}%"></div>
                            </div>
                            <div class="mt-1.5">
                                @if ($doVal > 0)
                                    <span class="jss-badge {{ $doOver ? 'jss-badge-danger' : 'jss-badge-success' }}">
                                        {{ $doOver ? 'OVER' : 'OK' }}
                                    </span>
                                @else
                                    <span class="text-xs text-gray-400 font-medium">Belum ada data</span>
                                @endif
                            </div>
                        </div>

                    </div>

                    <div class="jss-card-foot">
                        <a href="{{ \App\Filament\Resources\ShipmentTrackingResource::getUrl('index') }}" class="jss-link">
                            Lihat Detail Lead Time <x-heroicon-m-arrow-right class="w-3.5 h-3.5" />
                        </a>
                    </div>
                </div>

{{-- ══ COL RIGHT: PEMERIKSAAN UNIT ══ --}}
                <div class="lg:col-span-5 jss-card p-5 flex flex-col">
                    <div class="jss-card-head">
                        <h3 class="jss-card-title">Pemeriksaan Unit</h3>
                        <p class="jss-card-sub">Status inspeksi sebelum pengiriman</p>
                    </div>

                    @if ($insp['ng'] === 0 && $insp['belum'] === 0)
                        <div class="jss-banner-success mb-3">
                            <x-heroicon-m-check-circle class="w-4 h-4 text-emerald-600 shrink-0" />
                            <span class="text-xs font-semibold text-emerald-800">Semua unit siap dikirim. Tidak ada temuan NG.</span>
                        </div>
                    @endif

                    <div class="grid grid-cols-3 gap-3 flex-1">

                        <div class="jss-mini-card jss-mini-warning">
                            <div class="jss-mini-iconbox bg-amber-100">
                                <x-heroicon-o-magnifying-glass class="w-5 h-5 text-amber-600" />
                            </div>
                            <p class="jss-mini-num {{ $insp['belum'] > 0 ? 'text-amber-700' : 'text-gray-300' }}">{{ number_format($insp['belum']) }}</p>
                            <p class="jss-mini-label">Belum Periksa</p>
                        </div>

                        <div class="jss-mini-card jss-mini-success">
                            <div class="jss-mini-iconbox bg-emerald-100">
                                <x-heroicon-o-check-badge class="w-5 h-5 text-emerald-600" />
                            </div>
                            <p class="jss-mini-num text-emerald-700">{{ number_format($insp['sudah']) }}</p>
                            <p class="jss-mini-label">Sudah Periksa</p>
                        </div>

                        <div class="jss-mini-card {{ $insp['ng'] > 0 ? 'jss-mini-danger' : 'jss-mini-neutral' }}">
                            <div class="jss-mini-iconbox {{ $insp['ng'] > 0 ? 'bg-red-100' : 'bg-gray-100' }}">
                                <x-heroicon-o-x-circle class="w-5 h-5 {{ $insp['ng'] > 0 ? 'text-red-600' : 'text-gray-400' }}" />
                            </div>
                            <p class="jss-mini-num {{ $insp['ng'] > 0 ? 'text-red-600' : 'text-gray-300' }}">{{ number_format($insp['ng']) }}</p>
                            <p class="jss-mini-label">Temuan NG</p>
                        </div>

                    </div>

                    <div class="jss-card-foot">
                        <a href="{{ \App\Filament\Resources\ShipmentResource::getUrl('index') }}" class="jss-link">
                            Lihat Detail Pemeriksaan <x-heroicon-m-arrow-right class="w-3.5 h-3.5" />
                        </a>
                    </div>
                </div>

            </div>
        </section>

        {{-- ══════════════════════════════════════════════════════════════════
             ROW 4 — AKSES CEPAT (full width grid)
        ══════════════════════════════════════════════════════════════════ --}}
        <section class="jss-section">
            <div class="jss-section-head">
                <h2 class="jss-section-title">Akses Cepat</h2>
                <p class="jss-section-desc">Menu yang sering digunakan</p>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">

                <a href="{{ \App\Filament\Resources\ShipmentResource::getUrl('index') }}" class="jss-quick group">
                    <div class="flex items-start justify-between mb-1.5">
                        <div class="jss-quick-icon bg-blue-50 ring-blue-100/60 group-hover:bg-blue-100">
                            <x-heroicon-o-document-text class="w-6 h-6 text-blue-600" />
                        </div>
                        <x-heroicon-m-arrow-right class="w-5 h-5 text-gray-300 group-hover:text-primary-600 group-hover:translate-x-1 transition-all" />
                    </div>
                    <p class="jss-quick-title">Permintaan Pengiriman</p>
                    <p class="jss-quick-desc">Buat &amp; kelola SPPB</p>
                </a>

                <a href="{{ \App\Filament\Resources\ShipmentTrackingResource::getUrl('index') }}" class="jss-quick group">
                    <div class="flex items-start justify-between mb-1.5">
                        <div class="jss-quick-icon bg-emerald-50 ring-emerald-100/60 group-hover:bg-emerald-100">
                            <x-heroicon-o-map class="w-6 h-6 text-emerald-600" />
                        </div>
                        <x-heroicon-m-arrow-right class="w-5 h-5 text-gray-300 group-hover:text-primary-600 group-hover:translate-x-1 transition-all" />
                    </div>
                    <p class="jss-quick-title">Pelacakan &amp; Monitoring</p>
                    <p class="jss-quick-desc">Monitor perjalanan unit</p>
                </a>

                <a href="{{ \App\Filament\Pages\MonitoringKapalTam::getUrl() }}" class="jss-quick group">
                    <div class="flex items-start justify-between mb-1.5">
                        <div class="jss-quick-icon bg-indigo-50 ring-indigo-100/60 group-hover:bg-indigo-100">
                            <x-heroicon-o-globe-alt class="w-6 h-6 text-indigo-600" />
                        </div>
                        <x-heroicon-m-arrow-right class="w-5 h-5 text-gray-300 group-hover:text-primary-600 group-hover:translate-x-1 transition-all" />
                    </div>
                    <p class="jss-quick-title">Monitoring Kapal TAM</p>
                    <p class="jss-quick-desc">Monitoring kapal &amp; voyage</p>
                </a>

                <a href="{{ \App\Filament\Resources\VesselPlanResource::getUrl('index') }}" class="jss-quick group">
                    <div class="flex items-start justify-between mb-1.5">
                        <div class="jss-quick-icon bg-orange-50 ring-orange-100/60 group-hover:bg-orange-100">
                            <x-heroicon-o-calendar class="w-6 h-6 text-orange-600" />
                        </div>
                        <x-heroicon-m-arrow-right class="w-5 h-5 text-gray-300 group-hover:text-primary-600 group-hover:translate-x-1 transition-all" />
                    </div>
                    <p class="jss-quick-title">Perencanaan Kapal</p>
                    <p class="jss-quick-desc">Rencana voyage &amp; kapal</p>
                </a>

                <a href="{{ \App\Filament\Pages\EvaluasiVoyage::getUrl() }}" class="jss-quick group">
                    <div class="flex items-start justify-between mb-1.5">
                        <div class="jss-quick-icon bg-purple-50 ring-purple-100/60 group-hover:bg-purple-100">
                            <x-heroicon-o-chart-bar class="w-6 h-6 text-purple-600" />
                        </div>
                        <x-heroicon-m-arrow-right class="w-5 h-5 text-gray-300 group-hover:text-primary-600 group-hover:translate-x-1 transition-all" />
                    </div>
                    <p class="jss-quick-title">Evaluasi Voyage</p>
                    <p class="jss-quick-desc">Analisis KPI &amp; performa</p>
                </a>

            </div>
        </section>

        {{-- ══════════════════════════════════════════════════════════════════
             FOOTER
        ══════════════════════════════════════════════════════════════════ --}}
        <div class="jss-footer">
            <div class="flex items-center gap-1.5 text-xs text-gray-400">
                <x-heroicon-m-information-circle class="w-4 h-4" />
                <span>Terakhir diperbarui: {{ now()->translatedFormat('d M Y, H:i') }} WIB</span>
                <span class="mx-2 text-gray-300">·</span>
                <span>Semua waktu dalam WIB</span>
            </div>
            <button wire:click="$refresh" class="jss-link cursor-pointer">
                <x-heroicon-m-arrow-path class="w-4 h-4" />
                Perbarui Data
            </button>
        </div>

    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        (function () {
            const charts = {};

            function destroyAll() {
                Object.values(charts).forEach(c => c && c.destroy());
                Object.keys(charts).forEach(k => delete charts[k]);
            }

            function render() {
                destroyAll();

                const el = document.getElementById('tam-chart-data');
                if (!el) return;

                const kpi = JSON.parse(el.dataset.kpi);

                const ctxPerf = document.getElementById('perfChart');
                if (ctxPerf) {
                    const onTime = Number(kpi.on_time ?? 0);
                    const late   = Number(kpi.late   ?? 0);
                    const total  = onTime + late;
                    const pct    = total > 0 ? Math.round(onTime / total * 100) : 0;

                    const centerEl = document.getElementById('perf-center-pct');
                    if (centerEl) centerEl.textContent = pct + '%';

                    charts.perf = new Chart(ctxPerf, {
                        type: 'doughnut',
                        data: {
                            datasets: [{
                                data: total > 0 ? [onTime, late] : [1, 0],
                                backgroundColor: total > 0 ? ['#10B981', '#EF4444'] : ['#E5E7EB', '#E5E7EB'],
                                borderWidth: 0,
                            }],
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            cutout: '76%',
                            plugins: { legend: { display: false }, tooltip: { enabled: total > 0 } },
                        },
                    });
                }
            }

            document.addEventListener('DOMContentLoaded', render);
            window.addEventListener('charts-ready', render);
            document.addEventListener('livewire:navigated', render);
        })();
    </script>
    @endpush

</x-filament-panels::page>