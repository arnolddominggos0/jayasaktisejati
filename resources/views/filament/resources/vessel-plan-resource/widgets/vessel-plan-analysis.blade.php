{{--
    Health Strip — bagian dari Workspace Hero (Baseline Design Language v1.0).
    Sprint 14.3: bukan lagi 4 tile dashboard — hanya menjawab satu pertanyaan,
    "apakah jadwal ini sehat?": ETD Gap (satu-satunya angka ber-threshold SOP)
    + status card Risiko. Jumlah Jadwal sudah di Hero meta; Avg Sailing tetap
    di tab Review Jadwal (rumah analytics).
    Teks sekunder minimal gray-500 (WCAG AA); gray-400 hanya untuk dekoratif.
--}}
<div class="vp-kpi-strip rounded-xl border mb-2">

    <div class="flex items-center flex-wrap gap-x-8 gap-y-3 py-1">

        {{-- ETD Gap — fokus utama: angka besar, target sebagai subtitle --}}
        <div>
            <div class="text-xs font-medium text-gray-500">ETD Gap</div>
            <div class="mt-0.5 flex items-baseline gap-1.5">
                <span class="text-2xl font-semibold leading-none tracking-tight {{ $gapOk ? 'text-gray-800' : ($maxGap <= 10 ? 'text-amber-600' : 'text-red-600') }}">{{ $maxGap }}</span>
                <span class="text-sm text-gray-500">hari</span>
            </div>
            <div class="mt-1 text-xs text-gray-500">Target &le; {{ $idealGap }} hari</div>
        </div>

        <div class="vp-kpi-divider h-10 w-px hidden sm:block"></div>

        {{-- Risiko — status card kecil, warna semantik --}}
        <div class="rounded-lg border px-3.5 py-2 {{ $statusBg }} {{ $statusBorder }}">
            <div class="text-sm font-semibold leading-tight {{ $statusColor }}">{{ $statusLabel }}</div>
            <div class="mt-0.5 text-xs {{ $statusColor }}">{{ $statusSub }}</div>
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
