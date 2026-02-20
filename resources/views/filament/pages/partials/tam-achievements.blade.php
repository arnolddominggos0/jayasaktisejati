<div class="space-y-8">

    {{-- =========================
        KPI UTAMA (OTD / OTA / OTB / SLA)
    ========================== --}}
    <div class="grid grid-cols-4 gap-6">

        @foreach ([
            'otd' => 'OTD (On Time Departure)',
            'ota' => 'OTA (On Time Arrival)',
            'otb' => 'OTB (On Time Berthing)',
            'sla' => 'Transit SLA'
        ] as $key => $label)

            @php
                $okPercent = $achievement[$key]['ok_percent'] ?? 0;
                $ngPercent = $achievement[$key]['ng_percent'] ?? 0;
                $ok = $achievement[$key]['ok'] ?? 0;
                $total = $achievement[$key]['total'] ?? 0;

                $warna = 'text-red-600';

                if ($okPercent >= 90) {
                    $warna = 'text-green-600';
                } elseif ($okPercent >= 70) {
                    $warna = 'text-orange-500';
                }
            @endphp

            <div class="bg-white rounded-2xl border p-6 shadow-sm">

                <div class="text-xs text-gray-500 uppercase tracking-wide">
                    {{ $label }}
                </div>

                <div class="mt-4 flex items-end justify-between">

                    <div>
                        <div class="text-3xl font-bold {{ $warna }}">
                            {{ $okPercent }}%
                        </div>

                        <div class="text-xs text-gray-500 mt-1">
                            OK {{ $ok }} / {{ $total }}
                        </div>
                    </div>

                    <div class="text-sm font-semibold {{ $ngPercent > 0 ? 'text-red-600' : 'text-gray-400' }}">
                        NG {{ $ngPercent }}%
                    </div>

                </div>

            </div>

        @endforeach

    </div>


    {{-- =========================
        INSIGHT OPERASIONAL
    ========================== --}}
    <div class="grid grid-cols-2 gap-6">

        {{-- Rata-rata Keterlambatan Berangkat --}}
        <div class="bg-white rounded-2xl border p-6 shadow-sm">

            <div class="text-xs text-gray-500 uppercase tracking-wide">
                Rata-rata Keterlambatan Berangkat
            </div>

            <div class="mt-4 text-3xl font-bold text-orange-600">
                {{ $achievement['rata_rata_delay_berangkat'] ?? 0 }} jam
            </div>

            <div class="text-xs text-gray-500 mt-2">
                Berdasarkan selisih ETD dan ATD bulan berjalan
            </div>

        </div>


        {{-- Penyebab Keterlambatan Terbanyak --}}
        <div class="bg-white rounded-2xl border p-6 shadow-sm">

            <div class="text-xs text-gray-500 uppercase tracking-wide">
                Penyebab Keterlambatan Terbanyak
            </div>

            <div class="mt-4 text-2xl font-bold text-red-600">
                {{ $achievement['penyebab_terbanyak'] ?? '—' }}
            </div>

            <div class="text-xs text-gray-500 mt-2">
                Berdasarkan data voyage yang mengalami perubahan jadwal
            </div>

        </div>

    </div>

</div>