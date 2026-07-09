{{--
    Hero Summary — bagian dari Workspace Hero (Baseline Design Language v1.0).
    Sprint 14.4: maks. 3 blok, semua menjawab pertanyaan planner sehari-hari —
    "berapa unit bulan ini" (Rencana Muatan), "apakah ETD Gap aman" (ETD Gap),
    dan "apakah plan ini siap" (Verdict). Jumlah Jadwal sudah di Hero meta;
    Avg Sailing tetap di tab Review Jadwal (rumah analytics).
    Sprint 14.6: tanpa border & divider vertikal — whitespace antar metric
    (gap-x-12) sudah cukup memisahkan tiap blok (Gestalt: proximity), lebih
    sesuai prinsip "tenang, bukan banyak kotak/garis" daripada furniture
    tambahan. Background tint tetap dipertahankan supaya blok ini masih
    terbaca sebagai satu kesatuan ringkasan, bukan teks lepas.
    Teks sekunder minimal gray-500 (WCAG AA); gray-400 hanya untuk dekoratif.
--}}
<div class="vp-kpi-strip rounded-xl mb-2">

    <div class="flex items-center flex-wrap gap-x-12 gap-y-4">

        {{-- Rencana Muatan — context metric: apa yang benar-benar direncanakan
             planner (unit muatan), bukan jumlah kapal (sudah ada di Hero). --}}
        <div>
            <div class="text-xs font-medium text-gray-500">Rencana Muatan</div>
            <div class="mt-1 flex items-baseline gap-1.5">
                <span class="text-2xl font-semibold leading-none tracking-tight text-gray-800">{{ $cargoTotal }}</span>
                <span class="text-sm text-gray-500">unit</span>
            </div>
        </div>

        {{-- ETD Gap — fokus utama: angka besar, target sebagai subtitle --}}
        <div>
            <div class="text-xs font-medium text-gray-500">ETD Gap</div>
            <div class="mt-1 flex items-baseline gap-1.5">
                <span class="text-2xl font-semibold leading-none tracking-tight {{ $gapOk ? 'text-gray-800' : ($maxGap <= 10 ? 'text-amber-600' : 'text-red-600') }}">{{ $maxGap }}</span>
                <span class="text-sm text-gray-500">hari</span>
            </div>
            {{-- Sprint 14.3A — gray-600 medium: penjelas angka, sedikit lebih
                 kontras dari label tapi tetap subordinat terhadap angka. --}}
            <div class="mt-1 text-xs font-medium text-gray-600">Target &le; {{ $idealGap }} hari</div>
        </div>

        {{-- Decision Verdict — tipografis, bukan chip/card berlatar. Ikon +
             label = kesimpulan keputusan, bukan sekadar status mentah. --}}
        <div>
            <div class="text-[15px] font-bold leading-tight {{ $statusColor }}">
                <span aria-hidden="true">{{ $verdictIcon }}</span> {{ $statusLabel }}
            </div>
            <div class="mt-0.5 text-xs text-gray-600">{{ $statusSub }}</div>
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
