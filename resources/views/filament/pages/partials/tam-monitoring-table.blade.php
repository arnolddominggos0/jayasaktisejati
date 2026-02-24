<div class="space-y-8">

    @php
        $kritis = $rows->filter(fn($v) => $v->operational_status === 'delayed');

        $berlayar = $rows->filter(fn($v) => $v->operational_status === 'sailing');
    @endphp



    @if ($kritis->count())
        <div class="bg-red-50 border border-red-200 rounded-2xl p-6 space-y-4">
            <div class="font-semibold text-red-700 text-sm uppercase">
                🚨 Kritis – Terlambat
            </div>

            @foreach ($kritis as $v)
                @include('filament.pages.partials.voyage-card', ['v' => $v])
            @endforeach
        </div>
    @endif



    @if ($berlayar->count())
        <div class="bg-blue-50 border border-blue-200 rounded-2xl p-6 space-y-4">
            <div class="font-semibold text-blue-700 text-sm uppercase">
                🚢 Sedang Berlayar
            </div>

            @foreach ($berlayar as $v)
                @include('filament.pages.partials.voyage-card', ['v' => $v])
            @endforeach
        </div>
    @endif



    @if (!$kritis->count() && !$berlayar->count())
        <div class="bg-white border rounded-2xl p-8 text-center text-gray-500">
            Tidak ada pelayaran aktif pada periode ini.
        </div>
    @endif

</div>
