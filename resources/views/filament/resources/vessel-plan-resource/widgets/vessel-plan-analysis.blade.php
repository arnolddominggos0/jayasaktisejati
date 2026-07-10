{{--
    Planning Summary — ringkasan kondisi keseluruhan Vessel Plan (bukan
    dashboard KPI). Satu card Filament, tiga kolom: Jadwal, Rencana Muatan,
    ETD Gap. Tidak diulang di tempat lain di halaman ini. Typography
    (label kecil, value besar) mengikuti pola Stats Overview bawaan
    Filament, tapi tetap satu Section — bukan tiga stat card terpisah.
--}}
<x-filament::section heading="Ringkasan Perencanaan" compact>

    <div class="grid grid-cols-3 gap-x-8">

        <div>
            <div class="text-sm font-medium text-gray-500">Jadwal</div>
            <div class="mt-1 text-3xl font-semibold tracking-tight text-gray-950">{{ $scheduleCount }}</div>
        </div>

        <div>
            <div class="text-sm font-medium text-gray-500">Rencana Muatan</div>
            <div class="mt-1 text-3xl font-semibold tracking-tight text-gray-950">{{ $cargoTotal }} <span class="text-base font-normal text-gray-500">unit</span></div>
        </div>

        <div>
            <div class="text-sm font-medium text-gray-500">ETD Gap</div>
            <div class="mt-1 text-3xl font-semibold tracking-tight {{ $gapOk ? 'text-gray-950' : ($maxGap <= 10 ? 'text-amber-600' : 'text-red-600') }}">
                {{ $maxGap }} <span class="text-base font-normal text-gray-500">hari</span>
            </div>
            <div class="mt-1 text-sm text-gray-500">Target &le; {{ $idealGap }} hari</div>
        </div>

    </div>

    @if (!empty($violations))
        @php $isCritical = $riskLevel === 'critical'; @endphp
        <div class="mt-4 pt-3 border-t border-gray-100 text-xs {{ $isCritical ? 'text-red-700' : 'text-amber-700' }}">
            @foreach ($violations as $v)
                <div>{{ $v }}</div>
            @endforeach
        </div>
    @endif

</x-filament::section>
