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
    Sprint 14.7: kotak sudah full-width sejak 14.5, tapi 3 metric-nya
    ber-flex-start (mepet kiri) menyisakan ruang kosong lebar di kanan —
    membuat kartu TERLIHAT lebih pendek dari Workspace meski batas kotaknya
    identik. justify-between menyebar metric mengisi lebar penuh, sama
    seperti kolom tabel di bawahnya. Nol metric baru, nol wording berubah.
    Sprint 14.8: audit browser menemukan 3 kolom tidak sejajar top edge
    (items-center men-tengah-kan tiap kolom terhadap kolom tertinggi) dan
    kolom Verdict tidak punya "jangkar" sebesar angka 24px di kolom 1/2 —
    struktur baris (label->value->subtitle) disamakan + label "Status Plan"
    ditambahkan (bukan metric baru, hanya baris label yang sebelumnya hilang).
    Sprint 14.9 ("14.7" pada brief — nomor sudah terpakai, lanjut kronologis):
    flex+justify-between diganti CSS Grid 1fr/1fr/1.3fr (kolom Status lebih
    lebar karena teksnya lebih panjang) — grid memberi proporsi kolom yang
    stabil, tidak lagi bergantung pada lebar konten seperti flex. Angka value
    dinaikkan 24px->36px agar jadi jangkar visual yang jauh lebih kuat (Three
    KPI, One Surface). Padding vertikal naik (bukan horizontal) untuk napas
    tinggi ~120-132px. Nol border/divider/shadow/icon/badge baru — grid +
    alignment + typography saja.
--}}
<div class="vp-kpi-strip rounded-xl mb-2">

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

        {{-- Decision Verdict — tipografis, bukan chip/card berlatar. Label
             "Status Plan" (Sprint 14.8) menyamakan struktur label->value
             ->subtitle dengan 2 kolom lain, bukan metric/field baru. --}}
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
