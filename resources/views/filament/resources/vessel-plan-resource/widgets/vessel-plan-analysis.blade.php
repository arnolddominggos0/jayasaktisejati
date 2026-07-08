{{--
    KPI Strip — bagian dari Workspace Hero (Baseline Design Language v1.0,
    Design Freeze Sprint 13.8). Latar muted supaya sekunder terhadap area kerja.
    Warna/padding/divider dikendalikan lewat class vp-kpi-* di theme.css.
    Teks sekunder minimal gray-500 (WCAG AA); gray-400 hanya untuk dekoratif.
--}}
<div class="vp-kpi-strip rounded-xl border mb-2">

    <div class="flex items-center flex-wrap gap-x-6 gap-y-2">

        <div>
            <div class="text-[10px] uppercase tracking-wider font-semibold text-gray-500">Jumlah Jadwal</div>
            <div class="text-lg font-semibold leading-tight text-gray-800">{{ $total }}</div>
        </div>

        <div class="vp-kpi-divider h-8 w-px hidden sm:block"></div>

        <div>
            <div class="text-[10px] uppercase tracking-wider font-semibold text-gray-500">Avg Sailing</div>
            <div class="text-lg font-semibold leading-tight text-gray-800">
                {{ $sailingAvg }} <span class="text-xs font-normal text-gray-500">hari</span>
            </div>
        </div>

        <div class="vp-kpi-divider h-8 w-px hidden sm:block"></div>

        <div>
            <div class="text-[10px] uppercase tracking-wider font-semibold text-gray-500">Max ETD Gap</div>
            <div class="text-lg font-semibold leading-tight {{ $gapOk ? 'text-gray-800' : ($maxGap <= 10 ? 'text-amber-600' : 'text-red-600') }}">
                {{ $maxGap }} <span class="text-xs font-normal text-gray-500">/ target {{ $idealGap }} hari</span>
            </div>
        </div>

        <div class="ml-auto">
            <span class="vp-kpi-risk-chip inline-flex items-center gap-1.5 rounded-full border {{ $statusBg }} {{ $statusColor }} {{ $statusBorder }}">
                Risiko: {{ $statusLabel }}
            </span>
        </div>

    </div>

    @if (!empty($violations))
        @php $isCritical = $riskLevel === 'critical'; @endphp
        <div class="mt-2.5 pt-2.5 border-t border-gray-100 text-xs {{ $isCritical ? 'text-red-700' : 'text-amber-700' }}">
            @foreach ($violations as $v)
                <div>{{ $v }}</div>
            @endforeach
        </div>
    @endif

</div>
