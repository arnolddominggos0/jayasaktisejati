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
        $activities = $this->getRecentActivities();

        $sparkLabels = [];
        $sparkValues = [];

        if (isset($kpi['sparkline']) && is_array($kpi['sparkline'])) {
            foreach ($kpi['sparkline'] as $row) {
                $sparkLabels[] = $row['label'] ?? '';
                $sparkValues[] = $row['value'] ?? 0;
            }
        }
    @endphp

    <div class="mb-4">
        {{ $this->form }}
    </div>

    <div class="max-w-7xl mx-auto space-y-6">
        {{-- ===================== DASHBOARD UMUM (DEFAULT) ===================== --}}
        @if ($this->dashboardView === 'all')
            <div>
                <div class="flex items-center justify-between mb-3">
                    <div class="text-sm font-semibold text-gray-800">
                        Ringkasan Shipment Keseluruhan
                    </div>
                    <span class="text-xs text-gray-500">
                        Data gabungan seluruh shipment.
                    </span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                    {{-- TOTAL AKTIF --}}
                    <x-filament::card>
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="text-xs text-gray-500 uppercase">Total Shipment Aktif</div>
                                <div class="text-3xl font-semibold mt-1">{{ $kpi['totalAktif'] ?? 0 }}</div>
                                <div class="mt-1 h-6">
                                    <canvas id="spark-activity"></canvas>
                                </div>
                            </div>
                            <x-heroicon-o-truck class="w-9 h-9 text-gray-300" />
                        </div>
                    </x-filament::card>

                    {{-- PENDING PICKUP --}}
                    <x-filament::card>
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="text-xs text-gray-500 uppercase">Menunggu / Penjemputan</div>
                                <div class="text-3xl font-semibold mt-1">{{ $kpi['pendingPickup'] ?? 0 }}</div>
                            </div>
                            <x-heroicon-o-clipboard class="w-9 h-9 text-gray-300" />
                        </div>
                    </x-filament::card>

                    {{-- ARMADA --}}
                    <x-filament::card>
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="text-xs text-gray-500 uppercase">Armada Aktif</div>
                                <div class="text-3xl font-semibold mt-1">{{ $kpi['armadaAktif'] ?? 0 }}</div>
                            </div>
                            <x-heroicon-o-truck class="w-9 h-9 text-gray-300" />
                        </div>
                    </x-filament::card>

                    {{-- TRACKING TODAY --}}
                    <x-filament::card>
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="text-xs text-gray-500 uppercase">Aktivitas Tracking (hari ini)</div>
                                <div class="text-3xl font-semibold mt-1">{{ $kpi['aktivitasHariIni'] ?? 0 }}</div>
                            </div>
                            <x-heroicon-o-bolt class="w-9 h-9 text-indigo-500" />
                        </div>
                    </x-filament::card>
                </div>

                {{-- TREN & STATUS --}}
                <div class="grid grid-cols-1 xl:grid-cols-3 gap-4 mt-6">
                    <x-filament::card class="xl:col-span-2">
                        <div class="flex items-center justify-between">
                            <div class="text-sm font-semibold">Tren Shipment</div>
                            <span class="text-xs text-gray-500">
                                {{ $this->period === 'monthly' ? '12 bulan terakhir' : '12 minggu terakhir' }}
                            </span>
                        </div>
                        <div class="mt-4">
                            <canvas id="trendChart" height="90"></canvas>
                        </div>
                    </x-filament::card>

                    <x-filament::card>
                        <div class="text-sm font-semibold">Status Shipment</div>
                        <div class="mt-4">
                            <canvas id="statusDistChart" height="90"></canvas>
                            <p class="mt-2 text-[11px] text-gray-500">
                                Menunjukkan sebaran status semua shipment aktif.
                            </p>
                        </div>
                    </x-filament::card>
                </div>

                {{-- CUSTOMER & LEAD TIME --}}
                <div class="grid grid-cols-1 xl:grid-cols-3 gap-4 mt-6">
                    <x-filament::card class="xl:col-span-2">
                        <div class="text-sm font-semibold">Top Customer (bulan ini)</div>
                        <div class="mt-4">
                            @if (empty($top))
                                <div class="text-gray-500 text-sm">Belum ada data.</div>
                            @else
                                <div class="space-y-3">
                                    @foreach ($top as $row)
                                        <div class="flex items-center justify-between text-sm">
                                            <div>{{ $row['name'] }}</div>
                                            <div>{{ $row['total'] }} shipment</div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </x-filament::card>

                    <x-filament::card>
                        <div class="text-sm font-semibold">Rata-rata Lead Time</div>
                        <div class="mt-2 text-4xl font-semibold">{{ $lt['avg_days'] ?? 0 }} hari</div>
                        <div class="text-xs text-gray-500">Target: {{ $lt['target'] ?? 0 }} hari</div>

                        <div class="w-full bg-gray-200 rounded mt-3 h-2">
                            <div
                                class="h-2 rounded"
                                style="width: {{ $lt['progress'] ?? 0 }}%; background: {{ $this->brandHex }}"
                            ></div>
                        </div>
                    </x-filament::card>
                </div>

                {{-- TRACKING TERBARU --}}
                <x-filament::card class="mt-6">
                    <div class="text-sm font-semibold">Aktivitas Tracking Terbaru</div>

                    @if (empty($activities))
                        <div class="py-10 text-center text-gray-500 text-sm">
                            Belum ada aktivitas.
                        </div>
                    @else
                        <div class="divide-y text-sm">
                            @foreach ($activities as $a)
                                <div class="flex items-start justify-between py-3">
                                    <div>
                                        <div class="font-medium">{{ $a['shipment_code'] }}</div>
                                        <div class="text-xs text-gray-500">{{ $a['note'] }}</div>
                                    </div>
                                    <div class="text-right text-[11px]">
                                        <div class="font-semibold">{{ strtoupper($a['status']) }}</div>
                                        <div class="text-gray-500">{{ $a['who'] }} • {{ $a['when'] }}</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </x-filament::card>
            </div>
        @endif

        {{-- ===================== DASHBOARD TAM – MANADO ===================== --}}
        @if ($this->dashboardView === 'tam')
            <div>
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <div class="text-sm font-semibold text-gray-800">
                            Dashboard TAM – Manado
                        </div>
                        <div class="mt-1 inline-flex items-center px-2 py-0.5 rounded-full bg-blue-50 text-[11px] text-blue-700">
                            Fokus ke customer TAM & lane Jakarta–Manado
                        </div>
                    </div>
                    <span class="text-xs text-gray-500">
                        KPI TAM (Dwelling, Sailing, Dooring, Total Lead Time)
                    </span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                    <x-filament::card>
                        <div>
                            <div class="text-xs text-gray-500 uppercase">Total Shipment TAM</div>
                            <div class="text-3xl font-semibold">{{ $tam['total'] ?? 0 }}</div>
                            <div class="mt-1 text-[11px] text-gray-500">
                                Shipment yang masuk perhitungan KPI bulan ini.
                            </div>
                        </div>
                    </x-filament::card>

                    <x-filament::card>
                        <div>
                            <div class="text-xs text-gray-500 uppercase">On-Time TAM</div>
                            <div class="text-3xl font-semibold">{{ $tam['on_time_pct'] ?? 0 }}%</div>
                            <div class="mt-1 text-[11px] text-gray-500">
                                {{ $tam['on_time'] ?? 0 }} dari {{ $tam['total'] ?? 0 }} shipment on-time.
                            </div>
                        </div>
                    </x-filament::card>

                    <x-filament::card>
                        <div>
                            <div class="text-xs text-gray-500 uppercase">Late TAM</div>
                            <div class="text-3xl font-semibold text-red-600">{{ $tam['late'] ?? 0 }}</div>
                            <div class="mt-1 text-[11px] text-gray-500">
                                {{ $tam['late_pct'] ?? 0 }}% dari shipment TAM terlambat.
                            </div>
                        </div>
                    </x-filament::card>

                    <x-filament::card>
                        <div>
                            <div class="text-xs text-gray-500 uppercase">Total Lead Time vs Target</div>
                            <div class="text-3xl font-semibold">
                                {{ $tamLead['values'][3] ?? 0 }}%
                            </div>
                            <div class="mt-1 text-[11px] text-gray-500">
                                Target total {{ $tam['target_total'] ?? 19 }} hari.
                            </div>
                        </div>
                    </x-filament::card>
                </div>

                <div class="grid grid-cols-1 xl:grid-cols-3 gap-4 mt-6">
                    <x-filament::card>
                        <div class="flex items-center justify-between mb-2">
                            <div class="text-sm font-semibold">Ringkasan KPI TAM</div>
                            <div class="text-xs text-gray-500">Bulan ini</div>
                        </div>

                        @if (($tam['total'] ?? 0) === 0)
                            <div class="text-gray-500 text-sm">
                                Belum ada shipment TAM yang masuk perhitungan KPI pada bulan ini.
                            </div>
                        @else
                            <dl class="text-sm space-y-2">
                                <div class="flex justify-between">
                                    <dt>Total shipment TAM</dt>
                                    <dd>{{ $tam['total'] }}</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt>On-Time</dt>
                                    <dd>{{ $tam['on_time'] }} ({{ $tam['on_time_pct'] }}%)</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt>Late</dt>
                                    <dd>{{ $tam['late'] }} ({{ $tam['late_pct'] }}%)</dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt>Target total lead time</dt>
                                    <dd>{{ $tam['target_total'] }} hari</dd>
                                </div>
                            </dl>
                        @endif
                    </x-filament::card>

                    <x-filament::card>
                        <div class="flex items-center justify-between mb-2">
                            <div class="text-sm font-semibold">Shipment TAM LATE</div>
                            <div class="text-xs text-gray-500">10 terbaru</div>
                        </div>

                        @if (empty($tamLate))
                            <div class="text-gray-500 text-sm">Tidak ada shipment late.</div>
                        @else
                            <div class="space-y-2 text-sm max-h-64 overflow-y-auto">
                                @foreach ($tamLate as $row)
                                    <div class="flex justify-between">
                                        <div class="pr-2">
                                            <div class="font-medium">{{ $row['code'] }}</div>
                                            <div class="text-xs text-gray-500">
                                                {{ $row['summary'] ?? 'Detail KPI tidak tersedia.' }}
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            @if (! is_null($row['late_by']))
                                                <div class="text-red-600 font-semibold">
                                                    +{{ $row['late_by'] }} hari
                                                </div>
                                                <div class="text-[11px] text-gray-500">
                                                    di atas target
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </x-filament::card>

                    <x-filament::card>
                        <div class="text-sm font-semibold mb-3">
                            KPI Lead Time TAM
                        </div>
                        <canvas id="tamLeadChart" height="120"></canvas>
                        <div class="mt-2 text-[11px] text-gray-500 leading-relaxed">
                            100% berarti rata-rata tepat di target.
                            Di atas 100% artinya lebih lama dari target dan perlu perhatian.
                        </div>
                    </x-filament::card>
                </div>
            </div>
        @endif
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        const sparkLabels   = @json($sparkLabels);
        const sparkValues   = @json($sparkValues);
        const trendLabels   = @json($trend['labels'] ?? []);
        const trendValues   = @json($trend['series'] ?? []);
        const distLabels    = @json($dist['labels'] ?? []);
        const distValues    = @json($dist['values'] ?? []);
        const tamLeadLabels = @json($tamLead['labels'] ?? []);
        const tamLeadValues = @json($tamLead['values'] ?? []);

        document.addEventListener("DOMContentLoaded", function () {
            const brandHex = @json($this->brandHex);

            // Sparkline aktivitas
            const sparkCanvas = document.getElementById("spark-activity");
            if (sparkCanvas && sparkLabels.length > 0) {
                new Chart(sparkCanvas, {
                    type: "line",
                    data: {
                        labels: sparkLabels,
                        datasets: [{
                            data: sparkValues,
                            borderColor: brandHex,
                            tension: 0.4,
                            borderWidth: 2,
                            fill: false,
                            pointRadius: 0,
                        }],
                    },
                    options: {
                        responsive: true,
                        plugins: { legend: { display: false } },
                        scales: {
                            x: { display: false },
                            y: { display: false },
                        },
                    },
                });
            }

            // Tren shipment
            const trendCanvas = document.getElementById("trendChart");
            if (trendCanvas && trendLabels.length > 0) {
                new Chart(trendCanvas, {
                    type: "line",
                    data: {
                        labels: trendLabels,
                        datasets: [{
                            data: trendValues,
                            borderColor: brandHex,
                            tension: 0.3,
                            borderWidth: 3,
                            fill: false,
                        }],
                    },
                    options: {
                        responsive: true,
                        plugins: { legend: { display: false } },
                        scales: {
                            y: { beginAtZero: true },
                        },
                    },
                });
            }

            // Distribusi status
            const distCanvas = document.getElementById("statusDistChart");
            if (distCanvas && distLabels.length > 0) {
                new Chart(distCanvas, {
                    type: "bar",
                    data: {
                        labels: distLabels,
                        datasets: [{
                            data: distValues,
                            backgroundColor: brandHex,
                        }],
                    },
                    options: {
                        responsive: true,
                        plugins: { legend: { display: false } },
                        scales: {
                            y: { beginAtZero: true },
                        },
                    },
                });
            }

            // TAM KPI lead time
            const tamCanvas = document.getElementById("tamLeadChart");
            if (tamCanvas && tamLeadLabels.length > 0) {
                new Chart(tamCanvas, {
                    type: "bar",
                    data: {
                        labels: tamLeadLabels,
                        datasets: [{
                            data: tamLeadValues,
                            backgroundColor: ["#1D4ED8", "#10B981", "#F59E0B", "#EF4444"],
                        }],
                    },
                    options: {
                        responsive: true,
                        plugins: { legend: { display: false } },
                        scales: {
                            y: { beginAtZero: true, max: 160 },
                        },
                    },
                });
            }
        });
    </script>
</x-filament-panels::page>
