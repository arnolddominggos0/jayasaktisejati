<x-filament-panels::page>
    <div class="space-y-6">

        {{-- Header --}}
        <div class="fi-card">
            <div class="p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900">Laporan Audit April 2026</h2>
                        <p class="text-gray-500 mt-1">Periode: 1 - 16 April 2026 | Depo Tanjung Priok</p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm text-gray-500">Field Coordinator</p>
                        <p class="font-semibold">Andi Wijaya</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Summary Cards --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="fi-card p-4">
                <div class="text-3xl font-bold text-blue-600">{{ $stats['briefing_sessions'] ?? 0 }}</div>
                <div class="text-sm text-gray-500 mt-1">Briefing Sessions</div>
            </div>
            <div class="fi-card p-4">
                <div class="text-3xl font-bold text-green-600">{{ $stats['attendance_present'] ?? 0 }}/{{ $stats['total_attendance'] ?? 0 }}</div>
                <div class="text-sm text-gray-500 mt-1">Kehadiran MP</div>
            </div>
            <div class="fi-card p-4">
                <div class="text-3xl font-bold text-purple-600">{{ $stats['total_mp'] ?? 0 }}</div>
                <div class="text-sm text-gray-500 mt-1">Total MP Aktif</div>
            </div>
            <div class="fi-card p-4">
                <div class="text-3xl font-bold text-orange-600">{{ $stats['ppe_ok'] ?? 0 }}/{{ $stats['ppe_checked'] ?? 0 }}</div>
                <div class="text-sm text-gray-500 mt-1">APD Layak Pakai</div>
            </div>
        </div>

        {{-- Health Stats --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="fi-card p-4 bg-red-50">
                <div class="text-lg font-semibold text-red-700">Rata-rata Suhu</div>
                <div class="text-2xl font-bold text-red-600">{{ $stats['avg_temperature'] ?? 0 }}°C</div>
            </div>
            <div class="fi-card p-4 bg-blue-50">
                <div class="text-lg font-semibold text-blue-700">Rata-rata TD</div>
                <div class="text-2xl font-bold text-blue-600">{{ $stats['avg_bp'] ?? '120/80' }}</div>
            </div>
            <div class="fi-card p-4 bg-green-50">
                <div class="text-lg font-semibold text-green-700">Loading Selesai</div>
                <div class="text-2xl font-bold text-green-600">{{ $stats['loading_completed'] ?? 0 }}</div>
            </div>
            <div class="fi-card p-4 bg-indigo-50">
                <div class="text-lg font-semibold text-indigo-700">Total Loading</div>
                <div class="text-2xl font-bold text-indigo-600">{{ $stats['loading_sessions'] ?? 0 }}</div>
            </div>
        </div>

        {{-- Daily Briefing Table --}}
        <div class="fi-card">
            <div class="p-4 border-b">
                <h3 class="text-lg font-semibold">📋 Rekap Kehadiran Harian</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tanggal</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Hadir</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Absent</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Sakit</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($briefings as $briefing)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-medium">{{ $briefing['date'] }}</td>
                            <td class="px-4 py-3 text-center text-green-600 font-semibold">{{ $briefing['present'] }}</td>
                            <td class="px-4 py-3 text-center text-red-500">{{ $briefing['absent'] }}</td>
                            <td class="px-4 py-3 text-center text-orange-500">{{ $briefing['sick'] ?? 0 }}</td>
                            <td class="px-4 py-3 text-center">
                                @if($briefing['mp_check_status'] === 'approved')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Approved</span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">Pending</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Loading Sessions Checklist --}}
        <div class="fi-card">
            <div class="p-4 border-b">
                <h3 class="text-lg font-semibold">📦 Checklist Loading Session</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kode</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Tanggal</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">MP</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Att</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Health</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">APD</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Rack</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Equip</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Unit</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($recentLoading as $loading)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-mono text-xs">{{ $loading['code'] }}</td>
                            <td class="px-4 py-3 text-center">{{ $loading['date'] }}</td>
                            <td class="px-4 py-3 text-center">{{ $loading['mp_present'] }}/{{ $loading['mp_required'] }}</td>
                            <td class="px-4 py-3 text-center">{!! $loading['mp_attendance'] !!}</td>
                            <td class="px-4 py-3 text-center">{!! $loading['health_check'] !!}</td>
                            <td class="px-4 py-3 text-center">{!! $loading['apd_check'] !!}</td>
                            <td class="px-4 py-3 text-center">{!! $loading['rack_check'] !!}</td>
                            <td class="px-4 py-3 text-center">{!! $loading['equipment_check'] !!}</td>
                            <td class="px-4 py-3 text-center">{!! $loading['unit_check'] !!}</td>
                            <td class="px-4 py-3 text-center">
                                @if($loading['final_decision'] === 'go')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">GO</span>
                                @elseif($loading['final_decision'] === 'stop')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">STOP</span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">Progress</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</x-filament-panels::page>
