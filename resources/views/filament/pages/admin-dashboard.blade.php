<x-filament-panels::page>
    {{-- Global Filters --}}
    <div class="mb-4">
        {{ $this->form }}
    </div>

    {{-- KPI row --}}
    @php($kpi = $this->getKpis())
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
        <x-filament::card>
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm text-gray-500">Total Shipment Aktif</div>
                    <div class="text-3xl font-semibold mt-1">{{ number_format($kpi['totalAktif']) }}</div>
                </div>
                <x-heroicon-o-truck class="w-8 h-8 text-gray-400" />
            </div>
            <div class="mt-3">
                <canvas id="spark-aktivitas" height="40"></canvas>
            </div>
        </x-filament::card>

        <x-filament::card>
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm text-gray-500">Pending Pickup/Stuffing</div>
                    <div class="text-3xl font-semibold mt-1">{{ number_format($kpi['pendingPickup']) }}</div>
                </div>
                <x-heroicon-o-clock class="w-8 h-8 text-yellow-500" />
            </div>
        </x-filament::card>

        <x-filament::card>
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm text-gray-500">Armada Aktif</div>
                    <div class="text-3xl font-semibold mt-1">{{ number_format($kpi['armadaAktif']) }}</div>
                </div>
                <x-heroicon-o-cog-6-tooth class="w-8 h-8 text-green-600" />
            </div>
        </x-filament::card>

        <x-filament::card>
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm text-gray-500">Aktivitas Tracking (Hari ini)</div>
                    <div class="text-3xl font-semibold mt-1">{{ number_format($kpi['aktivitasHariIni']) }}</div>
                </div>
                <x-heroicon-o-bolt class="w-8 h-8 text-indigo-600" />
            </div>
        </x-filament::card>
    </div>

    {{-- Charts row --}}
    @php($trend = $this->getTrendSeries())
    @php($dist  = $this->getStatusDistribution())

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-4 mt-4">
        <x-filament::card class="xl:col-span-2">
            <div class="flex items-center justify-between">
                <div class="text-lg font-semibold">
                    Tren Shipment ({{ $this->period === 'monthly' ? '12 Bulan' : '12 Minggu' }})
                </div>
                <span class="text-xs text-gray-500">Update berkala</span>
            </div>
            <div class="mt-4">
                <canvas id="trendChart" height="90"></canvas>
            </div>
        </x-filament::card>

        <x-filament::card>
            <div class="text-lg font-semibold">Distribusi Status (Saat ini)</div>
            <div class="mt-4">
                <canvas id="distChart" height="90"></canvas>
            </div>
            <div class="mt-3 text-xs text-gray-500">Warna konsisten dengan badge status.</div>
        </x-filament::card>
    </div>

    {{-- Insights row --}}
    @php($top = $this->getTopCustomers())
    @php($lt  = $this->getLeadTimeSummary())

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-4 mt-4">
        <x-filament::card class="xl:col-span-2">
            <div class="flex items-center justify-between">
                <div class="text-lg font-semibold">Top 5 Customer (bulan ini)</div>
            </div>
            <div class="mt-4">
                @if(empty($top))
                    <div class="text-sm text-gray-500">Belum ada data.</div>
                @else
                    <div class="space-y-3">
                        @foreach($top as $row)
                            <div class="flex items-center justify-between">
                                <div class="truncate">{{ $row['name'] }}</div>
                                <div class="text-sm font-medium">{{ $row['total'] }} shipment</div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </x-filament::card>

        <x-filament::card>
            <div class="text-lg font-semibold">Rata-rata Lead Time</div>
            <div class="mt-2 text-3xl font-semibold">{{ $lt['avg_days'] }} hari</div>
            <div class="text-sm text-gray-500">Target: {{ $lt['target'] }} hari</div>
            <div class="w-full bg-gray-200 rounded-full h-2 mt-3">
                <div class="h-2 rounded-full" style="width: {{ $lt['progress'] }}%; background-color: {{ $this->brandHex }};"></div>
            </div>
        </x-filament::card>
    </div>

    {{-- Recent activities --}}
    @php($activities = $this->getRecentActivities())
    <x-filament::card class="mt-4">
        <div class="flex items-center justify-between">
            <div class="text-lg font-semibold">Aktivitas Tracking Terbaru</div>
        </div>

        @if(empty($activities))
            <div class="py-10 text-center text-sm text-gray-500">
                Belum ada tracking. Update terbaru akan muncul di sini begitu ada perubahan.
            </div>
        @else
            <div class="divide-y">
                @foreach($activities as $a)
                    <div class="py-3 flex items-start justify-between">
                        <div>
                            <div class="font-medium">{{ $a['shipment_code'] }}</div>
                            <div class="text-xs text-gray-500">{{ $a['note'] ?? '-' }}</div>
                        </div>
                        <div class="text-right">
                            <div class="text-sm">{{ strtoupper($a['status']) }}</div>
                            <div class="text-xs text-gray-500">{{ $a['who'] }} • {{ $a['when'] }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </x-filament::card>

    {{-- Chart.js --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1"></script>
    <script>
        // Sparkline
        const sparkCtx = document.getElementById('spark-aktivitas').getContext('2d');
        new Chart(sparkCtx, {
            type: 'line',
            data: {
                labels: @json(collect($kpi['sparkline'])->pluck('label')),
                datasets: [{
                    data: @json(collect($kpi['sparkline'])->pluck('value')),
                    tension: 0.3,
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: { x: { display: false }, y: { display: false } },
                elements: { point: { radius: 0 } }
            }
        });

        // Tren
        const trendCtx = document.getElementById('trendChart').getContext('2d');
        new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: @json($trend['labels']),
                datasets: [{
                    label: 'Shipment dibuat',
                    data: @json($trend['series']),
                    fill: false,
                    tension: 0.25,
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: {
                    x: { ticks: { autoSkip: true, maxTicksLimit: 12 } },
                    y: { beginAtZero: true, precision: 0 }
                }
            }
        });

        // Distribusi status
        const distCtx = document.getElementById('distChart').getContext('2d');
        new Chart(distCtx, {
            type: 'doughnut',
            data: {
                labels: @json($dist['labels']),
                datasets: [{ data: @json($dist['values']) }]
            },
            options: { plugins: { legend: { position: 'bottom' } } }
        });
    </script>
</x-filament-panels::page>
