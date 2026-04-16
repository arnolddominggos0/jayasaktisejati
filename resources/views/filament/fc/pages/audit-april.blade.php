<x-filament-panels::page>
    <div class="space-y-6">

        {{-- Header dengan Info Laporan --}}
        <div class="fi-card">
            <div class="bg-blue-900 text-white p-6 rounded-t-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-xl font-bold">LAPORAN HARIAN OPERASIONAL</h2>
                        <p class="text-blue-200 mt-1">PT. Jaya Sakti Sejati - Depo Tanjung Priok</p>
                    </div>
                    <div class="text-right bg-blue-800 p-3 rounded">
                        <p class="text-sm text-blue-200">Periode</p>
                        <p class="font-bold">1 - 16 April 2026</p>
                    </div>
                </div>
            </div>
            <div class="p-4 border-b bg-gray-50">
                <div class="grid grid-cols-3 gap-4 text-sm">
                    <div>
                        <span class="font-semibold text-gray-600">Field Coordinator:</span>
                        <span class="ml-2">{{ \Filament\Facades\Filament::auth()->user()->name ?? 'Suryadi' }}</span>
                    </div>
                    <div>
                        <span class="font-semibold text-gray-600">Depo:</span>
                        <span class="ml-2">Tanjung Priok</span>
                    </div>
                    <div>
                        <span class="font-semibold text-gray-600">Total HK:</span>
                        <span class="ml-2">16 Hari</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Ringkasan Statistik --}}
        <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
            <div class="fi-card p-4 text-center border-l-4 border-blue-500">
                <div class="text-2xl font-bold text-blue-600">{{ $stats['briefing_sessions'] ?? 0 }}</div>
                <div class="text-xs text-gray-500 mt-1">Briefing</div>
            </div>
            <div class="fi-card p-4 text-center border-l-4 border-green-500">
                <div class="text-2xl font-bold text-green-600">{{ $stats['attendance_present'] ?? 0 }}</div>
                <div class="text-xs text-gray-500 mt-1">Total Kehadiran</div>
            </div>
            <div class="fi-card p-4 text-center border-l-4 border-purple-500">
                <div class="text-2xl font-bold text-purple-600">{{ $stats['total_mp'] ?? 0 }}</div>
                <div class="text-xs text-gray-500 mt-1">Jumlah MP</div>
            </div>
            <div class="fi-card p-4 text-center border-l-4 border-orange-500">
                <div class="text-2xl font-bold text-orange-600">{{ $stats['loading_completed'] ?? 0 }}</div>
                <div class="text-xs text-gray-500 mt-1">Loading Selesai</div>
            </div>
            <div class="fi-card p-4 text-center border-l-4 border-red-500">
                <div class="text-2xl font-bold text-red-600">{{ $stats['avg_temperature'] ?? 0 }}°C</div>
                <div class="text-xs text-gray-500 mt-1">Rata-rata Suhu</div>
            </div>
        </div>

        {{-- Tabel Kehadiran Harian Per Orang --}}
        <div class="fi-card">
            <div class="p-4 border-b bg-gray-50">
                <h3 class="font-bold text-gray-800">DAFTAR HADIR MANPOWER - APRIL 2026</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-xs">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="px-2 py-2 text-left font-bold border">No</th>
                            <th class="px-2 py-2 text-left font-bold border w-40">Nama Lengkap</th>
                            <th class="px-2 py-2 text-center font-bold border">01</th>
                            <th class="px-2 py-2 text-center font-bold border">02</th>
                            <th class="px-2 py-2 text-center font-bold border">03</th>
                            <th class="px-2 py-2 text-center font-bold border">04</th>
                            <th class="px-2 py-2 text-center font-bold border">05</th>
                            <th class="px-2 py-2 text-center font-bold border">06</th>
                            <th class="px-2 py-2 text-center font-bold border">07</th>
                            <th class="px-2 py-2 text-center font-bold border">08</th>
                            <th class="px-2 py-2 text-center font-bold border">09</th>
                            <th class="px-2 py-2 text-center font-bold border">10</th>
                            <th class="px-2 py-2 text-center font-bold border bg-yellow-50">11</th>
                            <th class="px-2 py-2 text-center font-bold border bg-yellow-50">12</th>
                            <th class="px-2 py-2 text-center font-bold border">13</th>
                            <th class="px-2 py-2 text-center font-bold border">14</th>
                            <th class="px-2 py-2 text-center font-bold border">15</th>
                            <th class="px-2 py-2 text-center font-bold border">16</th>
                            <th class="px-2 py-2 text-center font-bold border bg-gray-200">Total Hadir</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white">
                        @foreach($manpowerList as $i => $mp)
                        <tr class="hover:bg-gray-50">
                            <td class="px-2 py-1 border text-center">{{ $i + 1 }}</td>
                            <td class="px-2 py-1 border font-medium">{{ $mp['name'] }}</td>
                            @foreach($mp['attendance'] as $day => $status)
                            <td class="px-2 py-1 border text-center">
                                @if($status === 'hadir')
                                    <span class="text-green-600 font-bold">✓</span>
                                @elseif($status === 'sakit')
                                    <span class="text-orange-600 font-bold" title="Sakit">S</span>
                                @elseif($status === 'izin')
                                    <span class="text-blue-600 font-bold" title="Izin">I</span>
                                @else
                                    <span class="text-red-500 font-bold">-</span>
                                @endif
                            </td>
                            @endforeach
                            <td class="px-2 py-1 border text-center bg-gray-50 font-bold">{{ $mp['total_present'] }}/{{ $mp['total_days'] }}</td>
                        </tr>
                        @endforeach
                        <tr class="bg-gray-100 font-bold">
                            <td colspan="2" class="px-2 py-1 border">TOTAL HADIR/HARI</td>
                            @foreach($dailyTotal as $day => $total)
                            <td class="px-2 py-1 border text-center">{{ $total['hadir'] }}</td>
                            @endforeach
                            <td class="px-2 py-1 border text-center bg-gray-200">{{ array_sum(array_column($dailyTotal, 'hadir')) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="p-2 bg-gray-50 border-t text-xs text-gray-500">
                <span class="font-bold">Keterangan:</span>
                <span class="text-green-600 ml-2">✓ = Hadir</span>
                <span class="text-orange-600 ml-2">S = Sakit</span>
                <span class="text-blue-600 ml-2">I = Izin</span>
                <span class="text-red-500 ml-2">- = Alpha</span>
            </div>
        </div>

        {{-- Detail Kesehatan & APD Per Hari --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Kesehatan Harian --}}
            <div class="fi-card">
                <div class="p-4 border-b bg-red-50">
                    <h3 class="font-bold text-red-800">PEMERIKSAAN KESEHATAN HARIAN</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead class="bg-red-50">
                            <tr>
                                <th class="px-2 py-2 text-left border">Tanggal</th>
                                <th class="px-2 py-2 text-center border">Total Hadir</th>
                                <th class="px-2 py-2 text-center border">Suhu (°C)</th>
                                <th class="px-2 py-2 text-center border">TD Sistolik</th>
                                <th class="px-2 py-2 text-center border">TD Diastolik</th>
                                <th class="px-2 py-2 text-center border">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($healthStats as $stat)
                            <tr class="hover:bg-gray-50">
                                <td class="px-2 py-1 border font-medium">{{ $stat['date'] }}</td>
                                <td class="px-2 py-1 border text-center">{{ $stat['total_present'] }}</td>
                                <td class="px-2 py-1 border text-center">{{ $stat['avg_temp'] }}</td>
                                <td class="px-2 py-1 border text-center">{{ $stat['avg_sys'] }}</td>
                                <td class="px-2 py-1 border text-center">{{ $stat['avg_dia'] }}</td>
                                <td class="px-2 py-1 border text-center">
                                    @if($stat['avg_temp'] <= 37.5)
                                        <span class="px-2 py-0.5 bg-green-100 text-green-700 rounded text-xs">Normal</span>
                                    @else
                                        <span class="px-2 py-0.5 bg-red-100 text-red-700 rounded text-xs">Perlu监控</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- APD Checklist --}}
            <div class="fi-card">
                <div class="p-4 border-b bg-yellow-50">
                    <h3 class="font-bold text-yellow-800">PEMERIKSAAN APD (ALAT PELINDUNG DIRI)</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead class="bg-yellow-50">
                            <tr>
                                <th class="px-2 py-2 text-left border">Tanggal</th>
                                <th class="px-2 py-2 text-center border">Total Cek</th>
                                <th class="px-2 py-2 text-center border">Helm</th>
                                <th class="px-2 py-2 text-center border">Rompi</th>
                                <th class="px-2 py-2 text-center border">Sepatu</th>
                                <th class="px-2 py-2 text-center border">Sarung Tangan</th>
                                <th class="px-2 py-2 text-center border">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($apdStats as $stat)
                            <tr class="hover:bg-gray-50">
                                <td class="px-2 py-1 border font-medium">{{ $stat['date'] }}</td>
                                <td class="px-2 py-1 border text-center">{{ $stat['total_checked'] }}</td>
                                <td class="px-2 py-1 border text-center text-green-600">{{ $stat['helm_ok'] }}/{{ $stat['total_checked'] }}</td>
                                <td class="px-2 py-1 border text-center text-green-600">{{ $stat['rompi_ok'] }}/{{ $stat['total_checked'] }}</td>
                                <td class="px-2 py-1 border text-center text-green-600">{{ $stat['sepatu_ok'] }}/{{ $stat['total_checked'] }}</td>
                                <td class="px-2 py-1 border text-center text-green-600">{{ $stat['sarung_tangan_ok'] }}/{{ $stat['total_checked'] }}</td>
                                <td class="px-2 py-1 border text-center">
                                    @if($stat['helm_ok'] == $stat['total_checked'])
                                        <span class="px-2 py-0.5 bg-green-100 text-green-700 rounded text-xs">Lengkap</span>
                                    @else
                                        <span class="px-2 py-0.5 bg-yellow-100 text-yellow-700 rounded text-xs">Ada Rusak</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Loading Session Checklist --}}
        <div class="fi-card">
            <div class="p-4 border-b bg-indigo-50">
                <h3 class="font-bold text-indigo-800">CHECKLIST LOADING SESSION</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-xs">
                    <thead class="bg-indigo-50">
                        <tr>
                            <th class="px-2 py-2 text-left border">Kode</th>
                            <th class="px-2 py-2 text-center border">Tanggal</th>
                            <th class="px-2 py-2 text-center border">MP</th>
                            <th class="px-2 py-2 text-center border">Kehadiran</th>
                            <th class="px-2 py-2 text-center border">Kesehatan</th>
                            <th class="px-2 py-2 text-center border">APD</th>
                            <th class="px-2 py-2 text-center border">Rack</th>
                            <th class="px-2 py-2 text-center border">Alat</th>
                            <th class="px-2 py-2 text-center border">Unit</th>
                            <th class="px-2 py-2 text-center border">Keputusan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($loadingSessions as $session)
                        <tr class="hover:bg-gray-50">
                            <td class="px-2 py-1 border font-mono">{{ $session['code'] }}</td>
                            <td class="px-2 py-1 border text-center">{{ $session['date'] }}</td>
                            <td class="px-2 py-1 border text-center">{{ $session['mp'] }}</td>
                            <td class="px-2 py-1 border text-center">{!! $session['attendance'] !!}</td>
                            <td class="px-2 py-1 border text-center">{!! $session['health'] !!}</td>
                            <td class="px-2 py-1 border text-center">{!! $session['apd'] !!}</td>
                            <td class="px-2 py-1 border text-center">{!! $session['rack'] !!}</td>
                            <td class="px-2 py-1 border text-center">{!! $session['equipment'] !!}</td>
                            <td class="px-2 py-1 border text-center">{!! $session['unit'] !!}</td>
                            <td class="px-2 py-1 border text-center">
                                @if($session['decision'] === 'GO')
                                    <span class="px-2 py-0.5 bg-green-500 text-white rounded text-xs font-bold">GO</span>
                                @elseif($session['decision'] === 'STOP')
                                    <span class="px-2 py-0.5 bg-red-500 text-white rounded text-xs font-bold">STOP</span>
                                @else
                                    <span class="px-2 py-0.5 bg-yellow-100 text-yellow-700 rounded text-xs">PROGRESS</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="p-3 bg-gray-50 border-t">
                <p class="text-xs text-gray-500">
                    <span class="font-bold">Catatan:</span>
                    Checkpoint wajib dilakukan sebelum loading dimulai. Semua item harus dalam kondisi OK sebelum keputusan GO diberikan.
                </p>
            </div>
        </div>

        {{-- Signature Section --}}
        <div class="fi-card">
            <div class="p-6 grid grid-cols-3 gap-8 text-center">
                <div>
                    <p class="text-sm text-gray-500 mb-8">Mengetahui,</p>
                    <p class="font-bold">Koordinator Lapangan</p>
                    <p class="text-sm text-gray-400 mt-12">{{ \Filament\Facades\Filament::auth()->user()->name ?? 'Suryadi' }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 mb-8">&nbsp;</p>
                    <p class="font-bold">Supervisor HSE</p>
                    <p class="text-sm text-gray-400 mt-12">_________________</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500 mb-8">&nbsp;</p>
                    <p class="font-bold">Admin Logistik</p>
                    <p class="text-sm text-gray-400 mt-12">_________________</p>
                </div>
            </div>
        </div>

    </div>
</x-filament-panels::page>
