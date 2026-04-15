<x-filament-panels::page>
    <div class="fi-card mb-6">
        <div class="p-6">
            <h2 class="text-xl font-bold mb-2">📊 Ringkasan Audit April 2026</h2>
            <p class="text-gray-500">Periode: 1 - 16 April 2026</p>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4 p-6 pt-0">
            <div class="fi-card-section">
                <div class="text-3xl font-bold text-blue-600">{{ $stats['briefing_sessions'] ?? 0 }}</div>
                <div class="text-sm text-gray-500">Briefing Sessions</div>
            </div>
            <div class="fi-card-section">
                <div class="text-3xl font-bold text-green-600">{{ $stats['attendance_present'] ?? 0 }}/{{ $stats['total_attendance'] ?? 0 }}</div>
                <div class="text-sm text-gray-500">Kehadiran MP</div>
            </div>
            <div class="fi-card-section">
                <div class="text-3xl font-bold text-purple-600">{{ $stats['total_mp'] ?? 0 }}</div>
                <div class="text-sm text-gray-500">Total MP</div>
            </div>
            <div class="fi-card-section">
                <div class="text-3xl font-bold text-orange-600">{{ $stats['avg_temperature'] ?? 0 }}°C</div>
                <div class="text-sm text-gray-500">Rata-rata Suhu</div>
            </div>
            <div class="fi-card-section">
                <div class="text-3xl font-bold text-red-600">{{ $stats['avg_bp'] ?? '120/80' }}</div>
                <div class="text-sm text-gray-500">Rata-rata TD</div>
            </div>
            <div class="fi-card-section">
                <div class="text-3xl font-bold text-teal-600">{{ $stats['ppe_ok'] ?? 0 }}/{{ $stats['ppe_checked'] ?? 0 }}</div>
                <div class="text-sm text-gray-500">APD OK</div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <div class="fi-card">
            <div class="p-4 border-b">
                <h3 class="text-lg font-semibold">📋 Briefing Harian</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left">Tanggal</th>
                            <th class="px-4 py-2 text-left">Depot</th>
                            <th class="px-4 py-2 text-center">Hadir</th>
                            <th class="px-4 py-2 text-center">Absent</th>
                            <th class="px-4 py-2 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($briefings as $briefing)
                        <tr class="border-t">
                            <td class="px-4 py-2">{{ $briefing['date'] }}</td>
                            <td class="px-4 py-2">{{ $briefing['depot'] }}</td>
                            <td class="px-4 py-2 text-center text-green-600 font-semibold">{{ $briefing['present'] }}</td>
                            <td class="px-4 py-2 text-center text-red-500">{{ $briefing['absent'] }}</td>
                            <td class="px-4 py-2 text-center">
                                @if($briefing['mp_check_status'] === 'approved')
                                    <span class="px-2 py-1 bg-green-100 text-green-700 rounded text-xs">Approved</span>
                                @else
                                    <span class="px-2 py-1 bg-yellow-100 text-yellow-700 rounded text-xs">Pending</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="fi-card">
            <div class="p-4 border-b">
                <h3 class="text-lg font-semibold">📦 Loading Sessions</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left">Kode</th>
                            <th class="px-4 py-2 text-center">MP</th>
                            <th class="px-4 py-2 text-center">Attendance</th>
                            <th class="px-4 py-2 text-center">Health</th>
                            <th class="px-4 py-2 text-center">APD</th>
                            <th class="px-4 py-2 text-center">Rack</th>
                            <th class="px-4 py-2 text-center">Equip</th>
                            <th class="px-4 py-2 text-center">Unit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentLoading as $loading)
                        <tr class="border-t">
                            <td class="px-4 py-2 font-mono text-xs">{{ $loading['code'] }}</td>
                            <td class="px-4 py-2 text-center">{{ $loading['mp_present'] }}/{{ $loading['mp_required'] }}</td>
                            <td class="px-4 py-2 text-center">{!! $loading['mp_attendance'] !!}</td>
                            <td class="px-4 py-2 text-center">{!! $loading['health_check'] !!}</td>
                            <td class="px-4 py-2 text-center">{!! $loading['apd_check'] !!}</td>
                            <td class="px-4 py-2 text-center">{!! $loading['rack_check'] !!}</td>
                            <td class="px-4 py-2 text-center">{!! $loading['equipment_check'] !!}</td>
                            <td class="px-4 py-2 text-center">{!! $loading['unit_check'] !!}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="fi-card">
        <div class="p-4 border-b">
            <h3 class="text-lg font-semibold">✅ Checklist Audit</h3>
        </div>
        <div class="p-4">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="p-4 bg-green-50 rounded-lg">
                    <div class="text-2xl mb-2">👥</div>
                    <div class="font-semibold">{{ $stats['attendance_present'] ?? 0 }} Kehadiran MP</div>
                    <div class="text-sm text-gray-500">Cek kehadiran harian</div>
                </div>
                <div class="p-4 bg-blue-50 rounded-lg">
                    <div class="text-2xl mb-2">🌡️</div>
                    <div class="font-semibold">{{ $stats['avg_temperature'] ?? 0 }}°C Suhu</div>
                    <div class="text-sm text-gray-500">Cek kesehatan</div>
                </div>
                <div class="p-4 bg-purple-50 rounded-lg">
                    <div class="text-2xl mb-2">🦺</div>
                    <div class="font-semibold">{{ $stats['ppe_ok'] ?? 0 }} APD OK</div>
                    <div class="text-sm text-gray-500">Cek perlengkapan</div>
                </div>
                <div class="p-4 bg-orange-50 rounded-lg">
                    <div class="text-2xl mb-2">🚛</div>
                    <div class="font-semibold">{{ $stats['loading_completed'] ?? 0 }} Loading Selesai</div>
                    <div class="text-sm text-gray-500">Checkpoint lengkap</div>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
