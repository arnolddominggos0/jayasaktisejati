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
        $cardClass =
            'bg-white dark:bg-slate-900/80 rounded-xl border border-gray-200 dark:border-slate-800 dark:shadow-sm dark:shadow-black/10';

        $tamLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun'];

        $tamTableData = [
            ['month' => 'Januari', 'dw' => 4.88, 'sl' => 10.47, 'dr' => 1.0],
            ['month' => 'Februari', 'dw' => 2.66, 'sl' => 10.34, 'dr' => 1.92],
            ['month' => 'Maret', 'dw' => 2.97, 'sl' => 9.57, 'dr' => 1.2],
            ['month' => 'April', 'dw' => 2.69, 'sl' => 11.15, 'dr' => 1.0],
            ['month' => 'Mei', 'dw' => 2.38, 'sl' => 10.43, 'dr' => 2.09],
            ['month' => 'Juni', 'dw' => 3.33, 'sl' => 11.16, 'dr' => 2.0],
        ];

        function getStatusColor($val, $threshold)
        {
            return $val > $threshold
                ? 'text-red-600 dark:text-red-400 font-bold bg-red-50 dark:bg-red-950/30 rounded'
                : 'text-gray-600 dark:text-slate-400';
        }

        $tamConfig = [
            'labels' => $tamLabels,
            'leadTime' => [
                'dwelling' => array_column($tamTableData, 'dw'),
                'sailing' => array_column($tamTableData, 'sl'),
                'dooring' => array_column($tamTableData, 'dr'),
                'standard' => [19, 19, 19, 19, 19, 19],
            ],
            'racking' => ['rack' => [88, 90, 85, 92, 80, 85], 'reg' => [12, 10, 15, 8, 20, 15]],
            'achievement' => ['ok' => [95, 98, 90, 92, 95, 91], 'ng' => [5, 2, 10, 8, 5, 9]],
        ];

        $ongoingMetrics = [
            [
                'title' => 'Unit di Tj Priok',
                'val' => '0',
                'icon' => 'heroicon-o-map-pin',
                'color' => 'text-blue-600 dark:text-blue-400',
                'bg' => 'bg-blue-50 dark:bg-blue-950/30',
            ],
            [
                'title' => 'Rata" Port',
                'val' => '0',
                'icon' => 'heroicon-o-clock',
                'color' => 'text-orange-600',
                'bg' => 'bg-orange-50',
            ],
            [
                'title' => 'Unit Sailing',
                'val' => '52',
                'icon' => 'heroicon-o-paper-airplane',
                'color' => 'text-indigo-600',
                'bg' => 'bg-indigo-50',
            ],
            [
                'title' => 'Max Sailing (D)',
                'val' => '588,24',
                'icon' => 'heroicon-o-chart-bar',
                'color' => 'text-purple-600',
                'bg' => 'bg-purple-50',
            ],
            [
                'title' => 'Remain Dooring',
                'val' => '53',
                'icon' => 'heroicon-o-truck',
                'color' => 'text-emerald-600',
                'bg' => 'bg-emerald-50',
            ],
            [
                'title' => 'Dwelling Bitung',
                'val' => '26.4',
                'icon' => 'heroicon-o-home-modern',
                'color' => 'text-cyan-600',
                'bg' => 'bg-cyan-50',
            ],
        ];
    @endphp

    <div class="space-y-5">
        <div class="rounded-xl p-5 bg-blue-700 text-white">
            <h1 class="text-xl font-bold">Dashboard Operasional</h1>
            <p class="mt-1 text-sm opacity-80">Pantau performa operasional dan aktivitas shipment</p>
        </div>

        <div class="{{ $cardClass }} p-5" id="filter-section">
            <div class="mb-3">
                <h2 class="text-base font-semibold text-gray-900 dark:text-white">Filter Operasional</h2>
                <p class="mt-0.5 text-sm text-gray-500 dark:text-slate-400">Pilih cabang, moda, dan periode</p>
            </div>
            {{ $this->form }}
        </div>

        @if ($this->dashboardView === 'all')
            <div wire:key="dashboard-all" class="animate-fade-in-up space-y-5">
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    <div
                        class="p-5 rounded-xl border border-gray-200 dark:border-slate-800 bg-white dark:bg-slate-900/80 dark:shadow-sm dark:shadow-black/10">
                        <p class="text-sm font-medium text-gray-500 dark:text-slate-400">Total Shipment Aktif</p>
                        <p class="mt-1.5 text-3xl font-bold text-gray-900 dark:text-white">
                            {{ number_format($kpi['totalAktif'] ?? 0) }}</p>
                        <div class="mt-3 h-10">
                            <canvas id="spark-activity"></canvas>
                        </div>
                    </div>

                    <div
                        class="p-5 rounded-xl border border-gray-200 dark:border-slate-800 bg-white dark:bg-slate-900/80 dark:shadow-sm dark:shadow-black/10">
                        <p class="text-sm font-medium text-gray-500 dark:text-slate-400">Menunggu Penjemputan</p>
                        <p class="mt-1.5 text-3xl font-bold text-gray-900 dark:text-white">
                            {{ number_format($kpi['pendingPickup'] ?? 0) }}</p>
                        <p class="text-xs mt-1 text-gray-500 dark:text-slate-400">Draft, Pending, Pickup</p>
                    </div>

                    <div
                        class="p-5 rounded-xl border border-gray-200 dark:border-slate-800 bg-white dark:bg-slate-900/80 dark:shadow-sm dark:shadow-black/10">
                        <p class="text-sm font-medium text-gray-500 dark:text-slate-400">Aktivitas Tracking Hari Ini</p>
                        <p class="mt-1.5 text-3xl font-bold text-gray-900 dark:text-white">
                            {{ number_format($kpi['aktivitasHariIni'] ?? 0) }}</p>
                        <p class="text-xs mt-1 text-gray-500 dark:text-slate-400">Update tracking terbaru</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
                    <div class="{{ $cardClass }} p-5 lg:col-span-2">
                        <div class="mb-3 flex items-center justify-between">
                            <div>
                                <h3 class="text-base font-semibold text-gray-900 dark:text-white">Tren Shipment</h3>
                                <p class="text-sm text-gray-500 dark:text-slate-400">Pergerakan shipment berdasarkan
                                    periode
                                </p>
                            </div>
                            <span
                                class="inline-flex items-center rounded-full bg-blue-100 dark:bg-blue-950/30 px-3 py-1 text-xs font-medium text-blue-800 dark:text-blue-400">
                                @if ($this->period === 'this_month')
                                    Bulan Ini
                                @elseif ($this->period === 'this_year')
                                    Tahun Ini
                                @else
                                    {{ \Carbon\Carbon::createFromFormat('Y-m', $this->periodMonth)->translatedFormat('F Y') }}
                                @endif
                            </span>
                        </div>
                        <div class="h-72">
                            <canvas id="trendChart"></canvas>
                        </div>
                    </div>

                    <div class="{{ $cardClass }} p-5">
                        <div class="mb-3">
                            <h3 class="text-base font-semibold text-gray-900 dark:text-white">Distribusi Status</h3>
                            <p class="mt-0.5 text-xs text-gray-500 dark:text-slate-400">Sebaran status shipment</p>
                        </div>
                        <div class="h-56">
                            <canvas id="statusDistChart"></canvas>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
                    <div class="{{ $cardClass }} p-5">
                        <div class="mb-3">
                            <h3 class="text-base font-semibold text-gray-900 dark:text-white">Lead Time Summary</h3>
                            <p class="mt-0.5 text-xs text-gray-500 dark:text-slate-400">Rata-rata waktu pengiriman</p>
                        </div>

                        <div class="flex items-end justify-between mb-3">
                            <div>
                                <p class="text-sm text-gray-500 dark:text-slate-400">Rata-rata</p>
                                <p class="mt-0.5 text-3xl font-bold text-gray-900 dark:text-white">
                                    {{ $lt['avg_days'] ?? 0 }} <span class="text-lg">hari</span></p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm text-gray-500 dark:text-slate-400">Target</p>
                                <p class="mt-0.5 text-xl font-semibold text-gray-900 dark:text-white">
                                    {{ $lt['target'] ?? 0 }} hari</p>
                            </div>
                        </div>

                        <div class="w-full bg-gray-200 dark:bg-slate-800 rounded-full h-2.5">
                            <div class="h-2.5 rounded-full bg-blue-600" style="width: {{ $lt['progress'] ?? 0 }}%">
                            </div>
                        </div>
                        <p class="mt-2 text-xs text-gray-500 dark:text-slate-400">Progress terhadap target</p>
                    </div>

                    <div class="{{ $cardClass }} p-5">
                        <div class="mb-3">
                            <h3 class="text-base font-semibold text-gray-900 dark:text-white">Top Customer</h3>
                            <p class="mt-0.5 text-xs text-gray-500 dark:text-slate-400">5 customer teratas</p>
                        </div>

                        @if (empty($top))
                            <div
                                class="flex flex-col items-center justify-center py-10 text-gray-500 dark:text-slate-400">
                                <x-heroicon-o-chart-pie class="w-8 h-8 opacity-35 mb-3" />
                                <p class="text-sm font-medium">Belum ada data customer</p>
                                @if ($this->period !== 'this_month')
                                    <button wire:click="$set('period', 'this_month')"
                                        class="mt-3 px-4 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-500 text-white text-sm">Tampilkan
                                        Bulan Ini</button>
                                @endif
                            </div>
                        @else
                            <div class="space-y-2.5">
                                @foreach ($top as $index => $customer)
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="flex-shrink-0 w-7 h-7 rounded-full bg-blue-100 dark:bg-blue-950/30 flex items-center justify-center">
                                            <span
                                                class="text-xs font-bold text-blue-600 dark:text-blue-400">{{ $index + 1 }}</span>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                                {{ $customer['name'] }}</p>
                                        </div>
                                        <div class="flex-shrink-0">
                                            <span
                                                class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 dark:bg-slate-800 text-gray-800 dark:text-slate-300">{{ $customer['total'] }}</span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
        @endif

        <div class="{{ $cardClass }} p-5">
            <div class="mb-3">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">Aktivitas Tracking Terbaru
                </h3>
                <p class="mt-0.5 text-xs text-gray-500 dark:text-slate-400">12 aktivitas terakhir</p>
            </div>

            @if (empty($activities))
                <div class="flex flex-col items-center justify-center py-10 text-gray-500 dark:text-slate-400">
                    <x-heroicon-o-clipboard class="w-8 h-8 opacity-35 mb-3" />
                    <p class="text-sm font-medium">Belum ada aktivitas</p>
                    @if ($this->period !== 'this_month')
                        <button wire:click="$set('period', 'this_month')"
                            class="mt-3 px-4 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-500 text-white text-sm">Tampilkan
                            Bulan Ini</button>
                    @endif
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-800">
                        <thead class="bg-gray-50 dark:bg-slate-900">
                            <tr>
                                <th
                                    class="px-3 py-2.5 text-left text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wider">
                                    Shipment</th>
                                <th
                                    class="px-3 py-2.5 text-left text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wider">
                                    Status</th>
                                <th
                                    class="px-3 py-2.5 text-left text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wider">
                                    Catatan</th>
                                <th
                                    class="px-3 py-2.5 text-left text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wider">
                                    User</th>
                                <th
                                    class="px-3 py-2.5 text-left text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wider">
                                    Waktu</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-slate-900/50 divide-y divide-gray-200 dark:divide-slate-800">
                            @foreach ($activities as $activity)
                                <tr class="hover:bg-gray-50 dark:hover:bg-slate-800/50 transition-colors duration-150">
                                    <td
                                        class="px-3 py-2.5 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $activity['shipment_code'] }}</td>
                                    <td class="px-3 py-2.5 whitespace-nowrap">
                                        <span
                                            class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 dark:bg-blue-950/30 text-blue-800 dark:text-blue-400">
                                            {{ strtoupper($activity['status']) }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-2.5 text-sm text-gray-500 dark:text-slate-400">
                                        {{ $activity['note'] ?? '-' }}</td>
                                    <td class="px-3 py-2.5 whitespace-nowrap text-sm text-gray-500 dark:text-slate-400">
                                        {{ $activity['who'] }}</td>
                                    <td class="px-3 py-2.5 whitespace-nowrap text-sm text-gray-500 dark:text-slate-400">
                                        {{ $activity['when'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    @if ($this->dashboardView === 'tam')
        <div wire:key="dashboard-tam" class="animate-fade-in-up space-y-5">

            <div
                class="flex flex-col md:flex-row justify-between items-start md:items-center gap-3 bg-white dark:bg-slate-900/80 p-5 rounded-xl border border-gray-200 dark:border-slate-800 dark:shadow-sm dark:shadow-black/10">
                <div>
                    <h1 class="text-xl font-bold text-gray-900 dark:text-white">Monitoring Pengiriman Unit</h1>
                    <p class="text-sm text-gray-500 dark:text-slate-400">Rute: Jakarta - Manado</p>
                </div>
                <div class="flex items-center gap-3">
                    <span
                        class="px-3 py-1 bg-gray-100 dark:bg-slate-800 text-gray-600 dark:text-slate-400 text-xs font-medium rounded-full">
                        Update: {{ now()->format('H:i') }}
                    </span>
                </div>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-12 gap-5">

                <div class="xl:col-span-7 flex flex-col gap-5">

                    <div class="grid grid-cols-3 gap-3">
                        <div
                            class="bg-white dark:bg-slate-900/80 p-3.5 rounded-xl border border-gray-200 dark:border-slate-800 dark:shadow-sm dark:shadow-black/10 flex items-center gap-3">
                            <div
                                class="p-2.5 bg-blue-100 text-blue-600 dark:bg-blue-950/30 dark:text-blue-400 rounded-lg">
                                <x-heroicon-o-cube class="w-5 h-5" />
                            </div>
                            <div>
                                <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider">
                                    Dwelling
                                </p>
                                <p class="text-base font-bold text-gray-900 dark:text-white">Std: 6 Hari</p>
                            </div>
                        </div>

                        <div
                            class="bg-white dark:bg-slate-900/80 p-3.5 rounded-xl border border-gray-200 dark:border-slate-800 dark:shadow-sm dark:shadow-black/10 flex items-center gap-3">
                            <div
                                class="p-2.5 bg-indigo-100 text-indigo-600 dark:bg-indigo-950/30 dark:text-indigo-400 rounded-lg">
                                <x-heroicon-o-lifebuoy class="w-5 h-5" />
                            </div>
                            <div>
                                <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider">
                                    Sailing
                                </p>
                                <p class="text-base font-bold text-gray-900 dark:text-white">Std: 10 Hari</p>
                            </div>
                        </div>

                        <div
                            class="bg-white dark:bg-slate-900/80 p-3.5 rounded-xl border border-gray-200 dark:border-slate-800 dark:shadow-sm dark:shadow-black/10 flex items-center gap-3">
                            <div
                                class="p-2.5 bg-emerald-100 text-emerald-600 dark:bg-emerald-950/30 dark:text-emerald-400 rounded-lg">
                                <x-heroicon-o-truck class="w-5 h-5" />
                            </div>
                            <div>
                                <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider">
                                    Dooring
                                </p>
                                <p class="text-base font-bold text-gray-900 dark:text-white">Std: 3 Hari</p>
                            </div>
                        </div>
                    </div>

                    <div
                        class="bg-white dark:bg-slate-900/80 rounded-xl border border-gray-200 dark:border-slate-800 dark:shadow-sm dark:shadow-black/10 overflow-hidden flex-1">
                        <div
                            class="px-5 py-3 border-b border-gray-200 dark:border-slate-800 flex justify-between items-center">
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Rincian Kinerja
                                Bulanan
                            </h3>
                            <span class="text-[11px] text-gray-500 italic">*Merah = melebihi standar</span>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left">
                                <thead
                                    class="bg-gray-50 dark:bg-slate-900 text-gray-500 dark:text-slate-400 font-medium">
                                    <tr>
                                        <th class="px-5 py-2.5">Bulan</th>
                                        <th class="px-5 py-2.5 text-center">Dwelling</th>
                                        <th class="px-5 py-2.5 text-center">Sailing</th>
                                        <th class="px-5 py-2.5 text-center">Dooring</th>
                                        <th class="px-5 py-2.5 text-right">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-slate-800">
                                    @foreach ($tamTableData as $row)
                                        <tr
                                            class="hover:bg-gray-50 dark:hover:bg-slate-800/50 transition-colors duration-150">
                                            <td class="px-5 py-2.5 font-medium text-gray-900 dark:text-white">
                                                {{ $row['month'] }}</td>
                                            <td class="px-5 py-2.5 text-center">
                                                <span
                                                    class="px-2 py-0.5 {{ getStatusColor($row['dw'], 6) }}">{{ number_format($row['dw'], 2) }}</span>
                                            </td>
                                            <td class="px-5 py-2.5 text-center">
                                                <span
                                                    class="px-2 py-0.5 {{ getStatusColor($row['sl'], 10) }}">{{ number_format($row['sl'], 2) }}</span>
                                            </td>
                                            <td class="px-5 py-2.5 text-center">
                                                <span
                                                    class="px-2 py-0.5 {{ getStatusColor($row['dr'], 3) }}">{{ number_format($row['dr'], 2) }}</span>
                                            </td>
                                            <td class="px-5 py-2.5 text-right">
                                                @if ($row['sl'] > 10 || $row['dw'] > 6)
                                                    <span
                                                        class="inline-flex items-center gap-1 text-[11px] font-medium text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-950/30 px-2 py-0.5 rounded-full">
                                                        <span class="w-1.5 h-1.5 bg-red-500 rounded-full"></span>
                                                        Warning
                                                    </span>
                                                @else
                                                    <span
                                                        class="inline-flex items-center gap-1 text-[11px] font-medium text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/30 px-2 py-0.5 rounded-full">
                                                        <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full"></span>
                                                        Sesuai
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

                <div
                    class="xl:col-span-5 bg-white dark:bg-slate-900/80 rounded-xl border border-gray-200 dark:border-slate-800 dark:shadow-sm dark:shadow-black/10 p-5 flex flex-col">
                    <div class="mb-4">
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Lead Time</h3>
                        <p class="text-xs text-gray-500 dark:text-slate-400">Breakdown per komponen</p>
                    </div>

                    <div class="flex flex-wrap gap-3 mb-3 text-xs font-medium text-gray-500 dark:text-slate-400">
                        <div class="flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-blue-400"></span> Dwelling
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-indigo-500"></span> Sailing
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-400"></span> Dooring
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="w-6 h-0.5 rounded-full bg-orange-400"></span> Std Limit
                        </div>
                    </div>

                    <div class="relative flex-1 min-h-[260px]">
                        <canvas id="tamMainChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-5">

                <div
                    class="bg-white dark:bg-slate-900/80 rounded-xl border border-gray-200 dark:border-slate-800 dark:shadow-sm dark:shadow-black/10 p-4">
                    <div class="flex justify-between items-center mb-3">
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Rak Available</h3>
                        <x-heroicon-o-square-3-stack-3d class="w-4 h-4 text-gray-400" />
                    </div>
                    <div class="h-36">
                        <canvas id="tamRackChart"></canvas>
                    </div>
                </div>

                <div
                    class="bg-white dark:bg-slate-900/80 rounded-xl border border-gray-200 dark:border-slate-800 dark:shadow-sm dark:shadow-black/10 p-4">
                    <div class="flex justify-between items-center mb-3">
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Pencapaian Lead Time
                        </h3>
                        <x-heroicon-o-trophy class="w-4 h-4 text-gray-400" />
                    </div>
                    <div class="h-36">
                        <canvas id="tamAchieveChart"></canvas>
                    </div>
                </div>

                <div class="xl:col-span-2 grid grid-cols-2 md:grid-cols-3 gap-3">
                    @foreach ($ongoingMetrics as $metric)
                        <div
                            class="bg-white dark:bg-slate-900/80 p-3.5 rounded-xl border border-gray-200 dark:border-slate-800 dark:shadow-sm dark:shadow-black/10 flex flex-col justify-between">
                            <div class="flex justify-between items-start">
                                <div class="p-1.5 rounded-lg {{ $metric['bg'] }} {{ $metric['color'] }}">
                                    @svg($metric['icon'], 'w-4 h-4')
                                </div>
                                @if ($loop->index == 3)
                                    <span class="flex h-2 w-2 relative">
                                        <span
                                            class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                                        <span class="relative inline-flex rounded-full h-2 w-2 bg-red-500"></span>
                                    </span>
                                @endif
                            </div>
                            <div class="mt-2">
                                <p
                                    class="text-[11px] font-medium text-gray-500 dark:text-slate-400 uppercase tracking-wide">
                                    {{ $metric['title'] }}</p>
                                <p class="text-xl font-bold text-gray-900 dark:text-white mt-0.5">
                                    {{ $metric['val'] }}
                                </p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        </div>
        </div>
    @endif

    <!-- chart -->
    @once
        <!-- charts -->
        @push('scripts')
            <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

            <script>
                window.tamCharts = window.tamCharts || {};

                function destroyTamCharts() {
                    Object.values(window.tamCharts).forEach(chart => {
                        if (chart) {
                            chart.destroy();
                        }
                    });

                    window.tamCharts = {};
                }

                function initTamCharts() {

                    destroyTamCharts();

                    const config = @json($tamConfig);

                    const isDark = () =>
                        document.documentElement.classList.contains('dark');

                    const gridColor = () =>
                        isDark() ?
                        'rgba(148,163,184,0.08)' :
                        '#e5e7eb';

                    const tickColor = () =>
                        isDark() ?
                        'rgb(148 163 184)' :
                        'rgb(100 116 139)';

                    /*
                    |--------------------------------------------------------------------------
                    | MAIN CHART
                    |--------------------------------------------------------------------------
                    */

                    const ctxMain = document.getElementById('tamMainChart');

                    if (ctxMain) {

                        window.tamCharts.main = new Chart(ctxMain, {
                            type: 'bar',

                            data: {
                                labels: config.labels,

                                datasets: [{
                                        type: 'line',
                                        label: 'Standard',
                                        data: config.leadTime.standard,
                                        borderColor: '#FB923C',
                                        borderWidth: 2,
                                        borderDash: [5, 5],
                                        pointRadius: 0,
                                        fill: false,
                                        order: 0,
                                    },

                                    {
                                        label: 'Dooring',
                                        data: config.leadTime.dooring,
                                        backgroundColor: '#34D399',
                                        borderRadius: 6,
                                        stack: 'stack',
                                    },

                                    {
                                        label: 'Sailing',
                                        data: config.leadTime.sailing,
                                        backgroundColor: '#6366F1',
                                        borderRadius: 6,
                                        stack: 'stack',
                                    },

                                    {
                                        label: 'Dwelling',
                                        data: config.leadTime.dwelling,
                                        backgroundColor: '#60A5FA',
                                        borderRadius: 6,
                                        stack: 'stack',
                                    },
                                ]
                            },

                            options: {
                                responsive: true,
                                maintainAspectRatio: false,

                                animation: {
                                    duration: 300
                                },

                                plugins: {
                                    legend: {
                                        display: false
                                    }
                                },

                                scales: {
                                    x: {
                                        stacked: true,

                                        grid: {
                                            display: false
                                        },

                                        ticks: {
                                            color: tickColor()
                                        }
                                    },

                                    y: {
                                        stacked: true,

                                        beginAtZero: true,

                                        grid: {
                                            color: gridColor()
                                        },

                                        ticks: {
                                            color: tickColor()
                                        }
                                    }
                                }
                            }
                        });
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | RACK CHART
                    |--------------------------------------------------------------------------
                    */

                    const ctxRack = document.getElementById('tamRackChart');

                    if (ctxRack) {

                        window.tamCharts.rack = new Chart(ctxRack, {
                            type: 'bar',

                            data: {
                                labels: config.labels,

                                datasets: [{
                                        label: 'Regular',
                                        data: config.racking.reg,
                                        backgroundColor: isDark() ?
                                            '#334155' :
                                            '#E5E7EB',

                                        borderRadius: 4,
                                    },

                                    {
                                        label: 'Rack',
                                        data: config.racking.rack,
                                        backgroundColor: '#3B82F6',
                                        borderRadius: 4,
                                    }
                                ]
                            },

                            options: {
                                responsive: true,
                                maintainAspectRatio: false,

                                animation: {
                                    duration: 300
                                },

                                plugins: {
                                    legend: {
                                        display: false
                                    }
                                },

                                scales: {
                                    x: {
                                        stacked: true,

                                        grid: {
                                            display: false
                                        },

                                        ticks: {
                                            color: tickColor(),
                                            font: {
                                                size: 10
                                            }
                                        }
                                    },

                                    y: {
                                        stacked: true,
                                        display: false,
                                        max: 100
                                    }
                                }
                            }
                        });
                    }

                    const ctxAch = document.getElementById('tamAchieveChart');

                    if (ctxAch) {

                        window.tamCharts.achieve = new Chart(ctxAch, {
                            type: 'bar',

                            data: {
                                labels: config.labels,

                                datasets: [{
                                        label: 'NG',
                                        data: config.achievement.ng,

                                        backgroundColor: isDark() ?
                                            '#7f1d1d' :
                                            '#FECACA',

                                        borderRadius: 4,
                                    },

                                    {
                                        label: 'OK',
                                        data: config.achievement.ok,

                                        backgroundColor: isDark() ?
                                            '#065f46' :
                                            '#10B981',

                                        borderRadius: 4,
                                    }
                                ]
                            },

                            options: {
                                responsive: true,
                                maintainAspectRatio: false,

                                animation: {
                                    duration: 300
                                },

                                plugins: {
                                    legend: {
                                        display: false
                                    }
                                },

                                scales: {
                                    x: {
                                        stacked: true,

                                        grid: {
                                            display: false
                                        },

                                        ticks: {
                                            color: tickColor(),
                                            font: {
                                                size: 10
                                            }
                                        }
                                    },

                                    y: {
                                        stacked: true,
                                        display: false,
                                        max: 100
                                    }
                                }
                            }
                        });
                    }
                }

                document.addEventListener('livewire:init', () => {

                    initTamCharts();

                    Livewire.hook('morph.updated', () => {

                        setTimeout(() => {
                            initTamCharts();
                        }, 50);

                    });
                });
            </script>
        @endpush

    @endonce
</x-filament-panels::page>
