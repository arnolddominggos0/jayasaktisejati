{{--
    Operational Summary — bagian bawah Object Header, menyatu dengan Hero
    (box disediakan oleh wrapper .fi-page-header-widgets, bukan di sini).
    Maksimal 3 blok, masing-masing menjawab satu pertanyaan planner sehari-
    hari: berapa unit bulan ini (Rencana Muatan), apakah ETD Gap aman (ETD
    Gap), apakah plan ini siap (Status Plan). Jumlah Jadwal sudah ada di
    Hero meta dan Avg Sailing adalah metrik analitis — keduanya sengaja
    tidak diulang di sini, rumahnya tab Review Jadwal.
    Kolom Status lebih lebar (1.3fr) karena teksnya secara konsisten lebih
    panjang dari dua kolom angka lainnya.
    Teks sekunder minimal gray-500 (WCAG AA); gray-400 hanya untuk dekoratif.
--}}
<div class="vp-kpi-strip">

    <div class="grid grid-cols-[1fr_1fr_1.3fr] gap-x-8">

        {{-- Rencana Muatan — context metric: apa yang benar-benar direncanakan
             planner (unit muatan), bukan jumlah kapal (sudah ada di Hero). --}}
        <div class="text-center">
            <div class="text-[13px] font-medium uppercase tracking-wide text-gray-500">Rencana Muatan</div>
            <div class="mt-1.5 flex items-baseline justify-center gap-2">
                <span class="text-4xl font-bold leading-none tracking-tight text-gray-800">{{ $cargoTotal }}</span>
                <span class="text-base font-medium text-gray-500">unit</span>
            </div>
        </div>

        {{-- ETD Gap — fokus utama: angka besar, target sebagai subtitle --}}
        <div class="text-center">
            <div class="text-[13px] font-medium uppercase tracking-wide text-gray-500">ETD Gap</div>
            <div class="mt-1.5 flex items-baseline justify-center gap-2">
                <span class="text-4xl font-bold leading-none tracking-tight {{ $gapOk ? 'text-gray-800' : ($maxGap <= 10 ? 'text-amber-600' : 'text-red-600') }}">{{ $maxGap }}</span>
                <span class="text-base font-medium text-gray-500">hari</span>
            </div>
            <div class="mt-1.5 text-sm text-gray-500">Target &le; {{ $idealGap }} hari</div>
        </div>

        {{-- Verdict — tipografis, bukan chip/card berlatar. Label "Status
             Plan" menyamakan struktur label->value->subtitle dengan 2
             kolom lain. --}}
        <div class="text-center">
            <div class="text-[13px] font-medium uppercase tracking-wide text-gray-500">Status Plan</div>
            <div class="mt-1.5 text-lg font-semibold leading-tight {{ $statusColor }}">
                <span aria-hidden="true">{{ $verdictIcon }}</span> {{ $statusLabel }}
            </div>
            <div class="mt-1.5 text-sm text-gray-500">{{ $statusSub }}</div>
        </div>

    </div>

    @if (!empty($violations))
        @php $isCritical = $riskLevel === 'critical'; @endphp
        <div class="mt-3 pt-2.5 border-t border-gray-100 text-xs {{ $isCritical ? 'text-red-700' : 'text-amber-700' }}">
            @foreach ($violations as $v)
                <div>{{ $v }}</div>
            @endforeach
        </div>
    @endif

</div>
