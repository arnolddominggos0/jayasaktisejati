<x-filament-panels::page>
    @php
        $kpi        = $this->getKpis();
        $trend      = $this->getTrendSeries();
        $dist       = $this->getStatusDistribution();
        $top        = $this->getTopCustomers();
        $lt         = $this->getLeadTimeSummary();
        $tam        = $this->getTamKpiSummary();
        $tamLate    = $this->getTamLateShipments();
        
        $tamLead    = $this->getTamLeadTimeSeries();
        $tamEval    = $this->getTamLeadTimeEvaluation();
        $tamPort    = $this->getTamPortStock();
        $activities = $this->getRecentActivities();

        $hasTamKpiData = !empty($tam) && ( ($tam['on_time'] ?? 0) > 0 || ($tam['late'] ?? 0) > 0 );
        $dashboardData = [
            'brandHex' => $this->brandHex,
            'spark'    => $kpi['sparkline'] ?? [],
            'trend'    => $trend,
            'dist'     => $dist,
            'tamEval'  => ['total' => ['ok' => $tam['on_time'] ?? 0, 'ng' => $tam['late'] ?? 0]],
        ];
        $cardClass = 'bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700';
        $dwMetric = $tamEval['dwelling'] ?? ['ok_pct' => 0, 'ng_pct' => 0];
        $saMetric = $tamEval['sailing'] ?? ['ok_pct' => 0, 'ng_pct' => 0];
        $doMetric = $tamEval['dooring'] ?? ['ok_pct' => 0, 'ng_pct' => 0];
        $ttMetric = $tamEval['total'] ?? ['ok_pct' => 0, 'ng_pct' => 0];
    @endphp

    @php
        function kpiColor($value, $target) {
            if ($value <= $target) return 'bg-green-50 text-green-700 border-green-300';
            if ($value <= $target + 2) return 'bg-yellow-50 text-yellow-700 border-yellow-300';
            return 'bg-red-50 text-red-700 border-red-300';
        }
    @endphp

    <div class="space-y-6">
        <div class="rounded-xl p-6 bg-gradient-to-r from-blue-600 to-blue-700 text-white shadow-md">
            <h1 class="text-2xl font-bold">Dashboard Operasional</h1>
            <p class="mt-1 text-sm opacity-80">Pantau performa operasional dan aktivitas shipment</p>
        </div>

        <div class="{{ $cardClass }} p-6" id="filter-section">
            <div class="mb-4">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Filter Operasional</h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Pilih cabang, moda, dan periode untuk melihat data spesifik</p>
            </div>
            {{ $this->form }}
        </div>

        <div class="{{ $this->dashboardView === 'all' ? '' : 'hidden' }} space-y-6">
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
                    <p class="mt-2 text-4xl font-bold text-gray-900">{{ number_format($kpi['aktivitasHariIni'] ?? 0) }}</p>
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
                        <span class="inline-flex items-center rounded-full bg-blue-100 dark:bg-blue-900 px-3 py-1 text-xs font-medium text-blue-800 dark:text-blue-200">
                            @if ($this->period === 'this_month') Bulan Ini
                            @elseif ($this->period === 'this_year') Tahun Ini
                            @else {{ \Carbon\Carbon::createFromFormat('Y-m', $this->periodMonth)->translatedFormat('F Y') }}
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
                            <p class="mt-1 text-4xl font-bold text-gray-900 dark:text-white">{{ $lt['avg_days'] ?? 0 }} <span class="text-xl">hari</span></p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm text-gray-600 dark:text-gray-400">Target</p>
                            <p class="mt-1 text-2xl font-semibold text-gray-900 dark:text-white">{{ $lt['target'] ?? 0 }} hari</p>
                        </div>
                    </div>

                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
                        <div class="h-3 rounded-full bg-gradient-to-r from-blue-500 to-blue-600" style="width: {{ $lt['progress'] ?? 0 }}%"></div>
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
                                <button wire:click="$set('period', 'this_month')" class="mt-4 px-4 py-1.5 rounded-md bg-blue-600 text-white text-sm shadow">Tampilkan Bulan Ini</button>
                            @endif
                        </div>
                    @else
                        <div class="space-y-3">
                            @foreach ($top as $index => $customer)
                                <div class="flex items-center gap-3">
                                    <div class="flex-shrink-0 w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900 flex items-center justify-center">
                                        <span class="text-sm font-bold text-blue-600 dark:text-blue-400">{{ $index + 1 }}</span>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $customer['name'] }}</p>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200">{{ $customer['total'] }} shipment</span>
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
                            <button wire:click="$set('period', 'this_month')" class="mt-4 px-4 py-1.5 rounded-md bg-blue-600 text-white text-sm shadow">Tampilkan Bulan Ini</button>
                        @endif
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-900">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Shipment</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Catatan</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">User</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Waktu</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach ($activities as $activity)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                        <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">{{ $activity['shipment_code'] }}</td>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200">
                                                {{ strtoupper($activity['status']) }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">{{ $activity['note'] ?? '-' }}</td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">{{ $activity['who'] }}</td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $activity['when'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        <div class="{{ $this->dashboardView === 'tam' ? '' : 'hidden' }} space-y-6">
            <div class="rounded-xl p-6 bg-gradient-to-r from-green-600 to-green-700 text-white shadow-md">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-xl font-bold">Dashboard TAM — Manado</h2>
                        <p class="mt-1 text-sm opacity-80">Fokus jalur Jakarta → Manado</p>
                    </div>
                    <div class="inline-flex items-center gap-2 bg-white/20 px-4 py-2 rounded-full text-sm">
                        <span class="font-semibold">Target SLA:</span>
                        <span>{{ $tam['target_total'] ?? 19 }} hari</span>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                <div class="p-6 border rounded-xl shadow-sm {{ kpiColor($tamLead['avg_days']['dwelling'] ?? 0, 6) }}">
                    <p class="text-sm font-medium">Dwelling</p>
                    <p class="mt-2 text-3xl font-bold">{{ round($tamLead['avg_days']['dwelling'] ?? 0, 1) }} <span class="text-lg">hari</span></p>
                    <div class="mt-2 flex items-center gap-2 text-xs">
                        <span class="text-green-600 dark:text-green-400">✓ {{ $dwMetric['ok_pct'] ?? 0 }}%</span>
                        <span class="text-red-600 dark:text-red-400">✗ {{ $dwMetric['ng_pct'] ?? 0 }}%</span>
                    </div>
                </div>

                <div class="p-6 border rounded-xl shadow-sm {{ kpiColor($tamLead['avg_days']['sailing'] ?? 0, 10) }}">
                    <p class="text-sm font-medium">Sailing</p>
                    <p class="mt-2 text-3xl font-bold">{{ round($tamLead['avg_days']['sailing'] ?? 0, 1) }} <span class="text-lg">hari</span></p>
                    <div class="mt-2 flex items-center gap-2 text-xs">
                        <span class="text-green-600 dark:text-green-400">✓ {{ $saMetric['ok_pct'] ?? 0 }}%</span>
                        <span class="text-red-600 dark:text-red-400">✗ {{ $saMetric['ng_pct'] ?? 0 }}%</span>
                    </div>
                </div>

                <div class="p-6 border rounded-xl shadow-sm {{ kpiColor($tamLead['avg_days']['dooring'] ?? 0, 2) }}">
                    <p class="text-sm font-medium">Dooring</p>
                    <p class="mt-2 text-3xl font-bold">{{ round($tamLead['avg_days']['dooring'] ?? 0, 1) }} <span class="text-lg">hari</span></p>
                    <div class="mt-2 flex items-center gap-2 text-xs">
                        <span class="text-green-600 dark:text-green-400">✓ {{ $doMetric['ok_pct'] ?? 0 }}%</span>
                        <span class="text-red-600 dark:text-red-400">✗ {{ $doMetric['ng_pct'] ?? 0 }}%</span>
                    </div>
                </div>

                <div class="p-6 border rounded-xl shadow-sm {{ kpiColor($tamLead['avg_days']['total'] ?? 0, $tam['target_total'] ?? 19) }}">
                    <p class="text-sm font-medium">Total Lead Time</p>
                    <p class="mt-2 text-3xl font-bold">{{ round($tamLead['avg_days']['total'] ?? 0, 1) }} <span class="text-lg">hari</span></p>
                    <div class="mt-2 flex items-center gap-2 text-xs">
                        <span class="text-green-600 dark:text-green-400">✓ {{ $ttMetric['ok_pct'] ?? 0 }}%</span>
                        <span class="text-red-600 dark:text-red-400">✗ {{ $ttMetric['ng_pct'] ?? 0 }}%</span>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <div class="{{ $cardClass }} p-6">
                    <div class="mb-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">KPI Lead Time TAM</h3>
                        <p class="mt-1 text-xs text-gray-600 dark:text-gray-400">Persentase OK vs NG untuk total lead time</p>
                    </div>
                    <div class="h-64 flex items-center justify-center">
                        <canvas id="tamTotalDonut"></canvas>
                    </div>
                    <div class="mt-4 grid grid-cols-2 gap-4 text-center">
                        <div>
                            <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ $tam['on_time'] ?? 0 }}</p>
                            <p class="text-xs text-gray-600 dark:text-gray-400">Shipment OK</p>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ $tam['late'] ?? 0 }}</p>
                            <p class="text-xs text-gray-600 dark:text-gray-400">Shipment NG</p>
                        </div>
                    </div>
                </div>

                <div class="{{ $cardClass }} p-6">
                    <div class="mb-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Shipment TAM Terlambat</h3>
                        <p class="mt-1 text-xs text-gray-600 dark:text-gray-400">10 shipment terlambat terbaru</p>
                    </div>

                    @if (empty($tamLate))
                        <div class="flex flex-col items-center justify-center py-10 text-gray-500">
                            <x-heroicon-o-clipboard class="w-12 h-12 opacity-60" />
                            <p class="mt-3 text-sm">Tidak ada shipment yang terlambat</p>
                            @if ($this->period !== 'this_month')
                                <button wire:click="$set('period', 'this_month')" class="mt-4 px-4 py-1.5 rounded-md bg-blue-600 text-white text-sm shadow">Tampilkan Bulan Ini</button>
                            @endif
                        </div>
                    @else
                        <div class="space-y-3 max-h-64 overflow-y-auto">
                            @foreach ($tamLate as $late)
                                <div class="flex items-start justify-between gap-3 p-3 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800">
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $late['code'] }}</p>
                                        <p class="mt-1 text-xs text-gray-600 dark:text-gray-400">{{ $late['summary'] ?? '-' }}</p>
                                    </div>
                                    @if (!is_null($late['late_by']))
                                        <div class="flex-shrink-0 text-right">
                                            <p class="text-lg font-bold text-red-600 dark:text-red-400">+{{ $late['late_by'] }}</p>
                                            <p class="text-xs text-gray-600 dark:text-gray-400">hari</p>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <div class="{{ $cardClass }} p-6">
                <div class="mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Port Stock TAM</h3>
                    <p class="mt-1 text-xs text-gray-600 dark:text-gray-400">Unit sudah di port, belum onboard</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                    <div class="text-center p-4 rounded-lg bg-blue-50 text-blue-700">
                        <p class="text-3xl font-bold">{{ $tamPort['total'] ?? 0 }}</p>
                        <p class="text-sm">Total Unit</p>
                    </div>
                    <div class="text-center p-4 rounded-lg bg-yellow-50 text-yellow-700">
                        <p class="text-3xl font-bold">{{ $tamPort['avg_age'] ?? 0 }}</p>
                        <p class="text-sm">Umur Rata-rata (hari)</p>
                    </div>
                    <div class="text-center p-4 rounded-lg bg-red-50 text-red-700">
                        <p class="text-3xl font-bold">{{ $tamPort['over_three'] ?? 0 }}</p>
                        <p class="text-sm">≥ 3 Hari</p>
                    </div>
                </div>

                @php $portDetail = $tamPort['items'] ?? []; @endphp

                @if (empty($portDetail))
                    <div class="flex flex-col items-center justify-center py-10 text-gray-500">
                        <x-heroicon-o-clipboard class="w-12 h-12 opacity-60" />
                        <p class="mt-3 text-sm">Tidak ada unit di port</p>
                        @if ($this->period !== 'this_month')
                            <button wire:click="$set('period', 'this_month')" class="mt-4 px-4 py-1.5 rounded-md bg-blue-600 text-white text-sm shadow">Tampilkan Bulan Ini</button>
                        @endif
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-900">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Shipment</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Route</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Status</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Umur (hari)</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach ($portDetail as $port)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                        <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">{{ $port['code'] ?? '-' }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">{{ $port['route'] ?? '-' }}</td>
                                        <td class="px-4 py-3 whitespace-nowrap text-xs text-gray-600 dark:text-gray-400">{{ $port['status'] ?? 'STACKING' }}</td>
                                        <td class="px-4 py-3 whitespace-nowrap text-right text-sm font-medium text-gray-900 dark:text-white">{{ $port['age_days'] ?? 0 }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @once
        @push('scripts')
            <script>window.__dashboardData = @json($dashboardData); window.__statusDistLabels = @json($dist['labels'] ?? []); window.__statusDistValues = @json($dist['values'] ?? []);</script>
            <script src="https://cdn.jsdelivr.net/npm/chart.js" defer></script>
            <script src="{{ asset('js/dashboard-charts.js') }}" defer></script>
        @endpush
    @endonce
</x-filament-panels::page>
