{{--
    Planning Summary — ringkasan kondisi keseluruhan Vessel Plan (bukan
    dashboard KPI). Satu card Filament, tiga kolom: Jadwal, Rencana Muatan,
    ETD Gap. Tidak diulang di tempat lain di halaman ini.
--}}
<x-filament::section heading="Ringkasan Perencanaan" compact>

    <div class="grid grid-cols-3 gap-x-8">

        <div>
            <div class="text-sm text-gray-500">Jadwal</div>
            <div class="mt-0.5 text-lg font-semibold text-gray-800">{{ $scheduleCount }}</div>
        </div>

        <div>
            <div class="text-sm text-gray-500">Rencana Muatan</div>
            <div class="mt-0.5 text-lg font-semibold text-gray-800">{{ $cargoTotal }} <span class="text-sm font-normal text-gray-500">unit</span></div>
        </div>

        <div>
            <div class="text-sm text-gray-500">ETD Gap</div>
            <div class="mt-0.5 text-lg font-semibold {{ $gapOk ? 'text-gray-800' : ($maxGap <= 10 ? 'text-amber-600' : 'text-red-600') }}">
                {{ $maxGap }} <span class="text-sm font-normal text-gray-500">hari</span>
            </div>
            <div class="text-xs text-gray-500">Target &le; {{ $idealGap }} hari</div>
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

</x-filament::section>
