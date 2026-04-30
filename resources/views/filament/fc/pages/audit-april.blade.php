<x-filament-panels::page>
    <div class="space-y-6">

        {{-- Header --}}
        <div class="fi-card overflow-hidden">
            <div class="bg-gradient-to-r from-blue-900 to-blue-700 text-white p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-2xl font-bold tracking-tight">DASHBOARD AUDIT</h2>
                        <p class="text-blue-200 mt-1">PT. Jaya Sakti Sejati - Depo Tanjung Priok</p>
                    </div>
                    <div class="text-right">
                        <div class="bg-blue-800 px-4 py-2 rounded-lg">
                            <p class="text-xs text-blue-300 uppercase tracking-wider">Periode</p>
                            <p class="text-lg font-bold">1 - 16 April 2026</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="p-4 bg-gray-50 border-b flex items-center justify-between">
                <div class="flex items-center gap-6 text-sm">
                    <div>
                        <span class="font-semibold text-gray-600">Field Coordinator:</span>
                        <span class="ml-2">{{ \Filament\Facades\Filament::auth()->user()->name ?? 'Tri Mulya' }}</span>
                    </div>
                    <div>
                        <span class="font-semibold text-gray-600">Depo:</span>
                        <span class="ml-2">Tanjung Priok</span>
                    </div>
                    <div>
                        <span class="font-semibold text-gray-600">Total Hari Kerja:</span>
                        <span class="ml-2">{{ $stats['briefing_sessions'] ?? 0 }} Hari</span>
                    </div>
                </div>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-green-100 text-green-800">
                    PERIODE APRIL 2026
                </span>
            </div>
        </div>

        {{-- KPI Cards --}}
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
            <div class="fi-card p-4 rounded-xl border-l-4 border-blue-500 bg-gradient-to-br from-white to-blue-50">
                <div class="text-xs text-gray-500 uppercase tracking-wider mb-1">Briefing</div>
                <div class="text-3xl font-bold text-blue-600">{{ $stats['briefing_sessions'] ?? 0 }}</div>
                <div class="text-xs text-gray-400 mt-1">Sesi Hari Kerja</div>
            </div>
            <div class="fi-card p-4 rounded-xl border-l-4 border-green-500 bg-gradient-to-br from-white to-green-50">
                <div class="text-xs text-gray-500 uppercase tracking-wider mb-1">Kehadiran</div>
                <div class="text-3xl font-bold text-green-600">{{ $stats['attendance_present'] ?? 0 }}</div>
                <div class="text-xs text-gray-400 mt-1">Total Hadir</div>
            </div>
            <div class="fi-card p-4 rounded-xl border-l-4 border-purple-500 bg-gradient-to-br from-white to-purple-50">
                <div class="text-xs text-gray-500 uppercase tracking-wider mb-1">MP Aktif</div>
                <div class="text-3xl font-bold text-purple-600">{{ $stats['total_mp'] ?? 0 }}</div>
                <div class="text-xs text-gray-400 mt-1">Orang</div>
            </div>
            <div class="fi-card p-4 rounded-xl border-l-4 border-yellow-500 bg-gradient-to-br from-white to-yellow-50">
                <div class="text-xs text-gray-500 uppercase tracking-wider mb-1">APD Layak</div>
                <div class="text-3xl font-bold text-yellow-600">{{ $stats['ppe_ok'] ?? 0 }}/{{ $stats['ppe_checked'] ?? 0 }}</div>
                <div class="text-xs text-gray-400 mt-1">
                    @php $ppeTotal = $stats['ppe_checked'] ?? 1; $ppeOk = $stats['ppe_ok'] ?? 0; @endphp
                    {{ $ppeTotal > 0 ? round(($ppeOk / $ppeTotal) * 100) : 0 }}% Layak
                </div>
            </div>
            <div class="fi-card p-4 rounded-xl border-l-4 border-indigo-500 bg-gradient-to-br from-white to-indigo-50">
                <div class="text-xs text-gray-500 uppercase tracking-wider mb-1">Loading</div>
                <div class="text-3xl font-bold text-indigo-600">{{ $stats['loading_completed'] ?? 0 }}</div>
                <div class="text-xs text-gray-400 mt-1">dari {{ $stats['loading_total'] ?? 0 }} sesi</div>
            </div>
            <div class="fi-card p-4 rounded-xl border-l-4 border-red-500 bg-gradient-to-br from-white to-red-50">
                <div class="text-xs text-gray-500 uppercase tracking-wider mb-1">Rata-rata Suhu</div>
                <div class="text-3xl font-bold text-red-600">{{ $stats['avg_temperature'] ?? 0 }}°C</div>
                <div class="text-xs text-gray-400 mt-1">{{ $stats['min_temperature'] ?? 0 }}°C - {{ $stats['max_temperature'] ?? 0 }}°C</div>
            </div>
        </div>

        {{-- Manpower Table + Health Summary --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- Daftar Manpower --}}
            <div class="lg:col-span-2 fi-card overflow-hidden">
                <div class="p-4 bg-blue-900 text-white">
                    <h3 class="font-bold text-lg">DAFTAR MANPOWER - PERIODE APRIL 2026</h3>
                    <p class="text-blue-200 text-sm mt-1">{{ count($manpowerList) }} orang terdaftar</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-blue-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-bold text-blue-800 border-b">No</th>
                                <th class="px-4 py-3 text-left font-bold text-blue-800 border-b">Nama Lengkap</th>
                                <th class="px-4 py-3 text-center font-bold text-blue-800 border-b">Kehadiran</th>
                                <th class="px-4 py-3 text-center font-bold text-blue-800 border-b">Persentase</th>
                                <th class="px-4 py-3 text-center font-bold text-blue-800 border-b">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($manpowerList as $i => $mp)
                            <tr class="hover:bg-blue-50 transition-colors {{ $i % 2 === 0 ? 'bg-white' : 'bg-gray-50' }}">
                                <td class="px-4 py-3 text-center border-b font-medium text-gray-600">{{ $i + 1 }}</td>
                                <td class="px-4 py-3 border-b font-semibold text-gray-800">{{ $mp['name'] }}</td>
                                <td class="px-4 py-3 text-center border-b">
                                    <span class="font-bold text-blue-700">{{ $mp['present'] }}</span>
                                    <span class="text-gray-400">/</span>
                                    <span class="text-gray-500">{{ $mp['total_days'] }}</span>
                                    <span class="text-gray-400 text-xs ml-1">hari</span>
                                </td>
                                <td class="px-4 py-3 text-center border-b">
                                    <div class="flex items-center justify-center gap-2">
                                        <div class="w-20 bg-gray-200 rounded-full h-2 overflow-hidden">
                                            <div class="h-2 rounded-full {{ $mp['percentage'] >= 80 ? 'bg-green-500' : ($mp['percentage'] >= 60 ? 'bg-yellow-500' : 'bg-red-500') }}" style="width: {{ $mp['percentage'] }}%"></div>
                                        </div>
                                        <span class="text-xs font-bold {{ $mp['percentage'] >= 80 ? 'text-green-600' : ($mp['percentage'] >= 60 ? 'text-yellow-600' : 'text-red-600') }}">{{ $mp['percentage'] }}%</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-center border-b">
                                    @if($mp['status'] === 'Aktif')
                                        <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-xs font-bold">Aktif</span>
                                    @elseif($mp['status'] === 'Cukup')
                                        <span class="px-3 py-1 bg-yellow-100 text-yellow-700 rounded-full text-xs font-bold">Cukup</span>
                                    @else
                                        <span class="px-3 py-1 bg-red-100 text-red-700 rounded-full text-xs font-bold">Kurang</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Kesehatan Summary --}}
            <div class="fi-card overflow-hidden">
                <div class="p-4 bg-red-600 text-white">
                    <h3 class="font-bold text-lg">RINGKASAN KESEHATAN</h3>
                </div>
                <div class="p-4 space-y-4">
                    {{-- Suhu --}}
                    <div class="bg-red-50 rounded-lg p-4">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center text-xl">🌡️</div>
                            <div>
                                <div class="font-bold text-gray-800">Suhu Tubuh</div>
                                <div class="text-xs text-gray-500">{{ $healthSummary['total_checks'] ?? 0 }} pemeriksaan</div>
                            </div>
                        </div>
                        <div class="grid grid-cols-3 gap-2 text-center">
                            <div class="bg-white rounded p-2">
                                <div class="text-xs text-gray-500">Min</div>
                                <div class="font-bold text-blue-600">{{ $healthSummary['temp_min'] ?? '-' }}°C</div>
                            </div>
                            <div class="bg-white rounded p-2">
                                <div class="text-xs text-gray-500">Rata-rata</div>
                                <div class="font-bold text-green-600">{{ $healthSummary['temp_avg'] ?? '-' }}°C</div>
                            </div>
                            <div class="bg-white rounded p-2">
                                <div class="text-xs text-gray-500">Maks</div>
                                <div class="font-bold text-orange-600">{{ $healthSummary['temp_max'] ?? '-' }}°C</div>
                            </div>
                        </div>
                    </div>

                    {{-- Tekanan Darah --}}
                    <div class="bg-purple-50 rounded-lg p-4">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center text-xl">💓</div>
                            <div>
                                <div class="font-bold text-gray-800">Tekanan Darah</div>
                                <div class="text-xs text-gray-500">Rata-rata</div>
                            </div>
                        </div>
                        <div class="bg-white rounded p-3 text-center">
                            <div class="text-3xl font-bold text-purple-600">{{ $healthSummary['sys_avg'] ?? '-' }}/{{ $healthSummary['dia_avg'] ?? '-' }}</div>
                            <div class="text-xs text-gray-500 mt-1">mmHg</div>
                        </div>
                    </div>

                    {{-- APD Summary --}}
                    <div class="bg-yellow-50 rounded-lg p-4">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-10 h-10 bg-yellow-100 rounded-lg flex items-center justify-center text-xl">🦺</div>
                            <div>
                                <div class="font-bold text-gray-800">APD (Alat Pelindung Diri)</div>
                                <div class="text-xs text-gray-500">Kondisi layak pakai</div>
                            </div>
                        </div>
                        <div class="space-y-2">
                            @foreach($apdSummary as $type => $data)
                            @php
                                $labels = ['helm' => 'Helm', 'rompi' => 'Rompi', 'sepatu' => 'Sepatu Safety', 'sarung_tangan' => 'Sarung Tangan'];
                                $colors = ['helm' => 'blue', 'rompi' => 'green', 'sepatu' => 'orange', 'sarung_tangan' => 'purple'];
                            @endphp
                            <div class="flex items-center justify-between text-sm">
                                <span class="font-medium text-gray-700">{{ $labels[$type] ?? $type }}</span>
                                <div class="flex items-center gap-2">
                                    <span class="font-bold">{{ $data['ok'] }}/{{ $data['total'] }}</span>
                                    <div class="w-16 bg-gray-200 rounded-full h-2">
                                        <div class="h-2 rounded-full {{ $data['percentage'] >= 95 ? 'bg-green-500' : ($data['percentage'] >= 80 ? 'bg-yellow-500' : 'bg-red-500') }}" style="width: {{ $data['percentage'] }}%"></div>
                                    </div>
                                    <span class="text-xs font-bold {{ $data['percentage'] >= 95 ? 'text-green-600' : 'text-yellow-600' }}">{{ $data['percentage'] }}%</span>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Briefing Schedule + Loading Summary --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- Jadwal Briefing --}}
            <div class="lg:col-span-2 fi-card overflow-hidden">
                <div class="p-4 bg-indigo-700 text-white">
                    <h3 class="font-bold text-lg">JADWAL BRIEFING HARIAN</h3>
                    <p class="text-indigo-200 text-sm mt-1">Periode 1-16 April 2026</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-indigo-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-bold text-indigo-800 border-b">Tanggal</th>
                                <th class="px-4 py-3 text-left font-bold text-indigo-800 border-b">PIC</th>
                                <th class="px-4 py-3 text-left font-bold text-indigo-800 border-b">Topik / Catatan</th>
                                <th class="px-4 py-3 text-center font-bold text-indigo-800 border-b">Peserta</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($briefings as $b)
                            <tr class="hover:bg-indigo-50 transition-colors">
                                <td class="px-4 py-2 border-b font-medium text-gray-700">{{ \Carbon\Carbon::parse($b['date'])->format('d M Y') }}</td>
                                <td class="px-4 py-2 border-b text-gray-600">{{ $b['coordinator'] }}</td>
                                <td class="px-4 py-2 border-b text-gray-600">{{ $b['notes'] }}</td>
                                <td class="px-4 py-2 border-b text-center">
                                    <span class="px-2 py-1 bg-indigo-100 text-indigo-700 rounded-full text-xs font-bold">{{ $b['attendees'] }} orang</span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Loading Summary --}}
            <div class="fi-card overflow-hidden">
                <div class="p-4 bg-emerald-700 text-white">
                    <h3 class="font-bold text-lg">LOADING SESSION</h3>
                    <p class="text-emerald-200 text-sm mt-1">Ringkasan Status</p>
                </div>
                <div class="p-4 space-y-4">
                    <div class="text-center bg-emerald-50 rounded-lg p-4">
                        <div class="text-4xl font-bold text-emerald-600">{{ $loadingSummary['completed'] ?? 0 }}</div>
                        <div class="text-sm text-gray-500 mt-1">Selesai dari {{ $loadingSummary['total'] ?? 0 }} sesi</div>
                    </div>

                    {{-- Progress Bar --}}
                    <div>
                        <div class="flex justify-between text-xs text-gray-500 mb-1">
                            <span>Persentase Selesai</span>
                            <span class="font-bold">{{ $loadingSummary['percentage'] ?? 0 }}%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-3 overflow-hidden">
                            <div class="h-3 rounded-full bg-gradient-to-r from-emerald-400 to-emerald-600 transition-all" style="width: {{ $loadingSummary['percentage'] ?? 0 }}%"></div>
                        </div>
                    </div>

                    {{-- Status Breakdown --}}
                    <div class="space-y-2">
                        @if(($loadingSummary['go'] ?? 0) > 0)
                        <div class="flex items-center justify-between bg-green-50 rounded-lg p-3">
                            <div class="flex items-center gap-2">
                                <span class="w-3 h-3 bg-green-500 rounded-full"></span>
                                <span class="text-sm font-medium text-gray-700">GO</span>
                            </div>
                            <span class="font-bold text-green-600">{{ $loadingSummary['go'] ?? 0 }}</span>
                        </div>
                        @endif
                        @if(($loadingSummary['stop'] ?? 0) > 0)
                        <div class="flex items-center justify-between bg-red-50 rounded-lg p-3">
                            <div class="flex items-center gap-2">
                                <span class="w-3 h-3 bg-red-500 rounded-full"></span>
                                <span class="text-sm font-medium text-gray-700">STOP</span>
                            </div>
                            <span class="font-bold text-red-600">{{ $loadingSummary['stop'] ?? 0 }}</span>
                        </div>
                        @endif
                        @if(($loadingSummary['progress'] ?? 0) > 0)
                        <div class="flex items-center justify-between bg-yellow-50 rounded-lg p-3">
                            <div class="flex items-center gap-2">
                                <span class="w-3 h-3 bg-yellow-500 rounded-full"></span>
                                <span class="text-sm font-medium text-gray-700">PROGRESS</span>
                            </div>
                            <span class="font-bold text-yellow-600">{{ $loadingSummary['progress'] ?? 0 }}</span>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

    </div>
</x-filament-panels::page>