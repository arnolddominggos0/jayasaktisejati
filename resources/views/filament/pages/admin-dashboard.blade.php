<x-filament-panels::page>
    @php
    $kpi = $this->getKpis();
    $trend = $this->getTrendSeries();
    $dist = $this->getStatusDistribution();
    $top = $this->getTopCustomers();
    $lt = $this->getLeadTimeSummary();
    $activities = $this->getRecentActivities();

    $dashboardData = [
    'brandHex' => $this->brandHex,
    'spark' => $kpi['sparkline'] ?? [],
    'trend' => $trend,
    'dist' => $dist,
    ];

    $cardClass = 'bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700';

    $tamLeadTime = $this->getTamLeadTimeSeries();
    $tamEval = $this->getTamLeadTimeEvaluation();
    $tamKpi = $this->getTamKpiSummary();
    $tamPortStock = $this->getTamPortStockSummary();
    $tamMonthly = $this->getTamMonthlyBreakdown();

    function getStatusColor($val, $threshold)
    {
    if ($val === null) {
    return 'text-gray-400';
    }
    return $val > $threshold ? 'text-red-600 font-bold bg-red-50 ring-1 ring-red-100 rounded' : 'text-gray-600';
    }

    $tamLabels = array_column($tamMonthly['rows'], 'month');
    $tamTargets = $tamMonthly['targets'];

    $tamConfig = [
    'labels' => $tamLabels,
    'leadTime' => [
    'dwelling' => array_column($tamMonthly['rows'], 'dw'),
    'sailing' => array_column($tamMonthly['rows'], 'sl'),
    'dooring' => array_column($tamMonthly['rows'], 'dr'),
    'standard' => array_fill(
    0,
    count($tamLabels),
    $tamTargets['dwelling'] + $tamTargets['sailing'] + $tamTargets['dooring'],
    ),
    ],
    'achievement' => [
    'labels' => ['Dwelling', 'Sailing', 'Dooring', 'Total'],
    'ok' => [
    $tamEval['dwelling']['ok_pct'] ?? 0,
    $tamEval['sailing']['ok_pct'] ?? 0,
    $tamEval['dooring']['ok_pct'] ?? 0,
    $tamEval['total']['ok_pct'] ?? 0,
    ],
    'ng' => [
    $tamEval['dwelling']['ng_pct'] ?? 0,
    $tamEval['sailing']['ng_pct'] ?? 0,
    $tamEval['dooring']['ng_pct'] ?? 0,
    $tamEval['total']['ng_pct'] ?? 0,
    ],
    ],
    ];

    $ongoingMetrics = [
    [
    'title' => 'Unit di Port',
    'val' => $tamPortStock['total'] ?? 0,
    'icon' => 'heroicon-o-map-pin',
    'color' => 'text-blue-600',
    'bg' => 'bg-blue-50',
    ],
    [
    'title' => 'Rata-rata Port (Hari)',
    'val' => number_format($tamPortStock['avg_age'] ?? 0, 1),
    'icon' => 'heroicon-o-clock',
    'color' => 'text-orange-600',
    'bg' => 'bg-orange-50',
    ],
    [
    'title' => 'Total Delivered',
    'val' => $tamKpi['total'] ?? 0,
    'icon' => 'heroicon-o-paper-airplane',
    'color' => 'text-indigo-600',
    'bg' => 'bg-indigo-50',
    ],
    [
    'title' => 'On Time (%)',
    'val' => number_format($tamKpi['on_time_pct'] ?? 0, 1) . '%',
    'icon' => 'heroicon-o-check-circle',
    'color' => 'text-emerald-600',
    'bg' => 'bg-emerald-50',
    ],
    [
    'title' => 'Late',
    'val' => $tamKpi['late'] ?? 0,
    'icon' => 'heroicon-o-exclamation-circle',
    'color' => 'text-red-600',
    'bg' => 'bg-red-50',
    ],
    [
    'title' => 'Over 3 Hari di Port',
    'val' => $tamPortStock['over_three'] ?? 0,
    'icon' => 'heroicon-o-home-modern',
    'color' => 'text-cyan-600',
    'bg' => 'bg-cyan-50',
    ],
    ];
    @endphp

    <div class="space-y-6">
        <div class="rounded-xl p-6 bg-gradient-to-r from-red-600 to-red-700 text-white shadow-md">
            <h1 class="text-2xl font-bold">Dashboard TAM</h1>
            <p class="mt-1 text-sm opacity-80">Monitoring KPI Lead Time — Rute Jakarta → Manado (Toyota Astra Motor)</p>
        </div>

        <div class="{{ $cardClass }} p-6" id="filter-section">
            <div class="mb-4">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Filter Operasional</h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Pilih cabang, moda, dan periode untuk melihat
                    data spesifik</p>
            </div>
            {{ $this->form }}
        </div>

        <div class="hidden space-y-6"> {{-- Dashboard Umum: hidden, TAM is default --}}
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <div class="p-6 rounded-xl shadow-sm border bg-white">
                    <p class="text-sm font-medium text-gray-600">Total Shipment Aktif</p>
                    <p class="mt-2 text-4xl font-bold text-gray-900">{{ number_format($kpi['totalAktif'] ?? 0) }}</p>
                    <div class="mt-4 h-12">
                        <canvas id="spark-activity"></canvas>
                    </div>
                </div>

                <div class="p-6 rounded-xl shadow-sm border bg-white">
                    <p class="text-sm font-medium text-gray-600">Menunggu Penjemputan</p>
                    <p class="mt-2 text-4xl font-bold text-gray-900">{{ number_format($kpi['pendingPickup'] ?? 0) }}</p>
                    <p class="text-xs mt-1 text-gray-500">Draft, Pending, Pickup</p>
                </div>

                <div class="p-6 rounded-xl shadow-sm border bg-white">
                    <p class="text-sm font-medium text-gray-600">Aktivitas Tracking Hari Ini</p>
                    <p class="mt-2 text-4xl font-bold text-gray-900">{{ number_format($kpi['aktivitasHariIni'] ?? 0) }}
                    </p>
                    <p class="text-xs mt-1 text-gray-500">Update tracking terbaru</p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <div class="{{ $cardClass }} p-6 lg:col-span-2">
                    <div class="mb-4 flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Tren Shipment</h3>
                            <p class="text-sm text-gray-500">Pergerakan shipment berdasarkan periode</p>
                        </div>
                        <span
                            class="inline-flex items-center rounded-full bg-blue-100 dark:bg-blue-900 px-3 py-1 text-xs font-medium text-blue-800 dark:text-blue-200">
                            @if ($this->period === 'this_month')
                            Bulan Ini
                            @elseif ($this->period === 'this_year')
                            Tahun Ini
                            @else
                            {{ \Carbon\Carbon::createFromFormat('Y-m', $this->periodMonth)->translatedFormat('F Y') }}
                            @endif
                        </span>
                    </div>
                    <div class="h-80">
                        <canvas id="trendChart"></canvas>
                    </div>
                </div>

                <div class="{{ $cardClass }} p-6">
                    <div class="mb-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Distribusi Status</h3>
                        <p class="mt-1 text-xs text-gray-600 dark:text-gray-400">Sebaran status shipment periode ini</p>
                    </div>
                    <div class="h-64">
                        <canvas id="statusDistChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <div class="{{ $cardClass }} p-6">
                    <div class="mb-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Lead Time Summary</h3>
                        <p class="mt-1 text-xs text-gray-600 dark:text-gray-400">Rata-rata waktu pengiriman shipment</p>
                    </div>

                    <div class="flex items-end justify-between mb-4">
                        <div>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Rata-rata Lead Time</p>
                            <p class="mt-1 text-4xl font-bold text-gray-900 dark:text-white">{{ $lt['avg_days'] ?? 0 }}
                                <span class="text-xl">hari</span>
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm text-gray-600 dark:text-gray-400">Target</p>
                            <p class="mt-1 text-2xl font-semibold text-gray-900 dark:text-white">
                                {{ $lt['target'] ?? 0 }} hari
                            </p>
                        </div>
                    </div>

                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
                        <div class="h-3 rounded-full bg-gradient-to-r from-blue-500 to-blue-600"
                            style="width: {{ $lt['progress'] ?? 0 }}%"></div>
                    </div>
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Progress terhadap target lead time</p>
                </div>

                <div class="{{ $cardClass }} p-6">
                    <div class="mb-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Top Customer</h3>
                        <p class="mt-1 text-xs text-gray-600 dark:text-gray-400">5 customer teratas periode ini</p>
                    </div>

                    @if (empty($top))
                    <div class="flex flex-col items-center justify-center py-10 text-gray-500">
                        <x-heroicon-o-chart-pie class="w-12 h-12 opacity-60" />
                        <p class="mt-3 text-sm">Belum ada data customer untuk periode ini</p>
                        @if ($this->period !== 'this_month')
                        <button wire:click="$set('period', 'this_month')"
                            class="mt-4 px-4 py-1.5 rounded-md bg-blue-600 text-white text-sm shadow">Tampilkan
                            Bulan Ini</button>
                        @endif
                    </div>
                    @else
                    <div class="space-y-3">
                        @foreach ($top as $index => $customer)
                        <div class="flex items-center gap-3">
                            <div
                                class="flex-shrink-0 w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900 flex items-center justify-center">
                                <span
                                    class="text-sm font-bold text-blue-600 dark:text-blue-400">{{ $index + 1 }}</span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                    {{ $customer['name'] }}
                                </p>
                            </div>
                            <div class="flex-shrink-0">
                                <span
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200">{{ $customer['total'] }}
                                    shipment</span>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @endif
                </div>
            </div>

            <div class="{{ $cardClass }} p-6">
                <div class="mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Aktivitas Tracking Terbaru</h3>
                    <p class="mt-1 text-xs text-gray-600 dark:text-gray-400">12 aktivitas tracking terakhir</p>
                </div>

                @if (empty($activities))
                <div class="flex flex-col items-center justify-center py-10 text-gray-500">
                    <x-heroicon-o-clipboard class="w-12 h-12 opacity-60" />
                    <p class="mt-3 text-sm">Belum ada aktivitas</p>
                    @if ($this->period !== 'this_month')
                    <button wire:click="$set('period', 'this_month')"
                        class="mt-4 px-4 py-1.5 rounded-md bg-blue-600 text-white text-sm shadow">Tampilkan
                        Bulan Ini</button>
                    @endif
                </div>
                @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr>
                                <th
                                    class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Shipment</th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Status</th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Catatan</th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    User</th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Waktu</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach ($activities as $activity)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                <td
                                    class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $activity['shipment_code'] }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200">
                                        {{ strtoupper($activity['status']) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                                    {{ $activity['note'] ?? '-' }}
                                </td>
                                <td
                                    class="px-4 py-3 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                                    {{ $activity['who'] }}
                                </td>
                                <td
                                    class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ $activity['when'] }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>
        </div>

        <div class="space-y-6"> {{-- Dashboard TAM: always visible --}}

            <div
                class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                <div>
                    <div class="flex items-center gap-3">
                        <div class="h-10 w-1 bg-red-600 rounded-full"></div>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Monitoring Pengiriman Unit</h1>
                            <p class="text-sm text-gray-500 font-medium">Rute: Jakarta - Manado</p>
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <span
                        class="px-3 py-1 bg-gray-100 text-gray-600 text-xs font-semibold rounded-full border border-gray-200">
                        Waktu Update: {{ now()->format('H:i') }}
                    </span>
                </div>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-12 gap-6">

                <div class="xl:col-span-7 flex flex-col gap-6">

                    <div class="grid grid-cols-3 gap-4">
                        <div
                            class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm flex items-center gap-4 relative overflow-hidden group hover:border-blue-200 transition">
                            <div
                                class="absolute right-0 top-0 w-24 h-full bg-gradient-to-l from-blue-50 to-transparent opacity-50">
                            </div>
                            <div class="p-3 bg-blue-100 text-blue-600 rounded-lg group-hover:scale-110 transition">
                                <x-heroicon-o-cube class="w-6 h-6" />
                            </div>
                            <div>
                                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Dwelling</p>
                                <p class="text-lg font-bold text-gray-900">
                                    {{ number_format($tamLeadTime['avg_days']['dwelling'] ?? 0, 1) }} <span
                                        class="text-xs font-normal text-gray-500">/ {{ $tamTargets['dwelling'] }}
                                        Hari</span>
                                </p>
                            </div>
                        </div>

                        <div
                            class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm flex items-center gap-4 relative overflow-hidden group hover:border-indigo-200 transition">
                            <div
                                class="absolute right-0 top-0 w-24 h-full bg-gradient-to-l from-indigo-50 to-transparent opacity-50">
                            </div>
                            <div class="p-3 bg-indigo-100 text-indigo-600 rounded-lg group-hover:scale-110 transition">
                                <x-heroicon-o-lifebuoy class="w-6 h-6" />
                            </div>
                            <div>
                                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Sailing</p>
                                <p class="text-lg font-bold text-gray-900">
                                    {{ number_format($tamLeadTime['avg_days']['sailing'] ?? 0, 1) }} <span
                                        class="text-xs font-normal text-gray-500">/ {{ $tamTargets['sailing'] }}
                                        Hari</span>
                                </p>
                            </div>
                        </div>

                        <div
                            class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm flex items-center gap-4 relative overflow-hidden group hover:border-emerald-200 transition">
                            <div
                                class="absolute right-0 top-0 w-24 h-full bg-gradient-to-l from-emerald-50 to-transparent opacity-50">
                            </div>
                            <div
                                class="p-3 bg-emerald-100 text-emerald-600 rounded-lg group-hover:scale-110 transition">
                                <x-heroicon-o-truck class="w-6 h-6" />
                            </div>
                            <div>
                                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Dooring</p>
                                <p class="text-lg font-bold text-gray-900">
                                    {{ number_format($tamLeadTime['avg_days']['dooring'] ?? 0, 1) }} <span
                                        class="text-xs font-normal text-gray-500">/ {{ $tamTargets['dooring'] }}
                                        Hari</span>
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden flex-1">
                        <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
                            <h3 class="font-bold text-gray-800">Rincian Kinerja Bulanan</h3>
                            <span class="text-xs text-gray-400 italic">*Cell yang berwarna merah berarti melebihi
                                standar</span>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left">
                                <thead class="bg-gray-50 text-gray-500 font-medium">
                                    <tr>
                                        <th class="px-6 py-3">Bulan</th>
                                        <th class="px-6 py-3 text-center">Dwelling (Hari)</th>
                                        <th class="px-6 py-3 text-center">Sailing (Hari)</th>
                                        <th class="px-6 py-3 text-center">Dooring (Hari)</th>
                                        <th class="px-6 py-3 text-right">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach ($tamMonthly['rows'] as $row)
                                    <tr class="hover:bg-gray-50 transition">
                                        <td class="px-6 py-3 font-medium text-gray-900">{{ $row['month'] }}</td>
                                        <td class="px-6 py-3 text-center">
                                            @if ($row['dw'] !== null)
                                            <span
                                                class="px-2 py-1 {{ getStatusColor($row['dw'], $tamTargets['dwelling']) }}">
                                                {{ number_format($row['dw'], 2) }}
                                            </span>
                                            @else
                                            <span class="text-gray-400">—</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-3 text-center">
                                            @if ($row['sl'] !== null)
                                            <span
                                                class="px-2 py-1 {{ getStatusColor($row['sl'], $tamTargets['sailing']) }}">
                                                {{ number_format($row['sl'], 2) }}
                                            </span>
                                            @else
                                            <span class="text-gray-400">—</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-3 text-center">
                                            @if ($row['dr'] !== null)
                                            <span
                                                class="px-2 py-1 {{ getStatusColor($row['dr'], $tamTargets['dooring']) }}">
                                                {{ number_format($row['dr'], 2) }}
                                            </span>
                                            @else
                                            <span class="text-gray-400">—</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-3 text-right">
                                            @if ($row['dw'] === null && $row['sl'] === null && $row['dr'] === null)
                                            <span class="text-xs text-gray-400">—</span>
                                            @elseif(($row['sl'] ?? 0) > $tamTargets['sailing'] || ($row['dw'] ?? 0) > $tamTargets['dwelling'])
                                            <span
                                                class="inline-flex items-center gap-1 text-xs font-medium text-red-600 bg-red-50 px-2 py-0.5 rounded-full border border-red-100">
                                                <span
                                                    class="w-1.5 h-1.5 bg-red-500 rounded-full animate-pulse"></span>
                                                Warning
                                            </span>
                                            @else
                                            <span
                                                class="inline-flex items-center gap-1 text-xs font-medium text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-100">
                                                <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full"></span>
                                                Sesuai Std
                                            </span>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="xl:col-span-5 bg-white rounded-xl border border-gray-200 shadow-sm p-6 flex flex-col">
                    <div class="mb-6">
                        <h3 class="font-bold text-gray-800">Lead Time</h3>
                        <p class="text-sm text-gray-500">Rata-rata lead time per bulan (6 bulan terakhir)</p>
                    </div>

                    <div class="flex flex-wrap gap-4 mb-4 text-xs font-medium text-gray-600">
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full bg-blue-400"></span> Dwelling
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full bg-indigo-500"></span> Sailing
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full bg-emerald-400"></span> Dooring
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="w-8 h-1 rounded-full bg-orange-400"></span> Std Limit
                        </div>
                    </div>

                    <div class="relative flex-1 min-h-[300px]">
                        <canvas id="tamMainChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">

                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="font-bold text-gray-800 text-sm">KPI On Time</h3>
                        <x-heroicon-o-presentation-chart-line class="w-5 h-5 text-gray-400" />
                    </div>
                    <div class="h-40">
                        <canvas id="tamRackChart"></canvas>
                    </div>
                </div>

                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="font-bold text-gray-800 text-sm">Pencapaian Lead Time</h3>
                        <x-heroicon-o-trophy class="w-5 h-5 text-gray-400" />
                    </div>
                    <div class="h-40">
                        <canvas id="tamAchieveChart"></canvas>
                    </div>
                </div>

                <div class="xl:col-span-2 grid grid-cols-2 md:grid-cols-3 gap-4">
                    @foreach ($ongoingMetrics as $metric)
                    <div
                        class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm flex flex-col justify-between hover:shadow-md transition group">
                        <div class="flex justify-between items-start">
                            <div
                                class="p-2 rounded-lg {{ $metric['bg'] }} {{ $metric['color'] }} group-hover:scale-110 transition">
                                @svg($metric['icon'], 'w-5 h-5')
                            </div>
                            @if ($loop->index == 3)
                            <span class="flex h-2 w-2 relative">
                                <span
                                    class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2 w-2 bg-red-500"></span>
                            </span>
                            @endif
                        </div>
                        <div class="mt-3">
                            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">
                                {{ $metric['title'] }}
                            </p>
                            <p class="text-2xl font-bold text-gray-900 mt-1">{{ $metric['val'] }}</p>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

        </div>

    </div>

    {{-- Single source of truth for all chart data --}}
    <div id="tam-chart-data"
         data-config='@json($tamConfig)'
         data-kpi='@json($tamKpi)'
         style="display:none"></div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        (function () {
            /** @type {Record<string, Chart>} */
            const charts = {};

            function destroyAll() {
                Object.values(charts).forEach(c => c && c.destroy());
                Object.keys(charts).forEach(k => delete charts[k]);
            }

            function render() {
                destroyAll();

                const el = document.getElementById('tam-chart-data');
                if (!el) return;

                const config = JSON.parse(el.dataset.config);
                const kpi    = JSON.parse(el.dataset.kpi);

                const ctxMain = document.getElementById('tamMainChart');
                if (ctxMain) {
                    charts.main = new Chart(ctxMain, {
                        type: 'bar',
                        data: {
                            labels: config.labels,
                            datasets: [
                                {
                                    type: 'line',
                                    label: 'Standard',
                                    data: config.leadTime.standard,
                                    borderColor: '#FB923C',
                                    borderWidth: 2,
                                    borderDash: [5, 5],
                                    pointRadius: 0,
                                    fill: false,
                                },
                                { label: 'Dooring',  data: config.leadTime.dooring,  backgroundColor: '#34D399', stack: 'lead' },
                                { label: 'Sailing',  data: config.leadTime.sailing,  backgroundColor: '#6366F1', stack: 'lead' },
                                { label: 'Dwelling', data: config.leadTime.dwelling, backgroundColor: '#60A5FA', stack: 'lead' },
                            ],
                        },
                        options: { responsive: true, maintainAspectRatio: false },
                    });
                }

                const ctxRack = document.getElementById('tamRackChart');
                if (ctxRack) {
                    charts.rack = new Chart(ctxRack, {
                        type: 'doughnut',
                        data: {
                            labels: ['On Time', 'Late'],
                            datasets: [{
                                data: [Number(kpi.on_time ?? 0), Number(kpi.late ?? 0)],
                                backgroundColor: ['#10B981', '#EF4444'],
                            }],
                        },
                        options: { responsive: true, maintainAspectRatio: false },
                    });
                }

                const ctxAch = document.getElementById('tamAchieveChart');
                if (ctxAch) {
                    charts.achieve = new Chart(ctxAch, {
                        type: 'bar',
                        data: {
                            labels: config.achievement.labels,
                            datasets: [
                                { label: 'NG', data: config.achievement.ng, backgroundColor: '#FECACA' },
                                { label: 'OK', data: config.achievement.ok, backgroundColor: '#10B981' },
                            ],
                        },
                        options: { responsive: true, maintainAspectRatio: false },
                    });
                }
            }

            // Initial render — fires once after full page load
            document.addEventListener('DOMContentLoaded', render);

            // After Livewire re-renders (post-DOM morph) — no setTimeout needed
            // $this->dispatch('charts-ready') in PHP fires AFTER Livewire morphs the DOM
            window.addEventListener('charts-ready', render);

            // Filament SPA navigation
            document.addEventListener('livewire:navigated', render);
        })();
    </script>
    @endpush
</x-filament-panels::page>