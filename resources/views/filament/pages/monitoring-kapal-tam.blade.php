<x-filament-panels::page>
    <div class="space-y-8">

        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold">Monitoring Kapal TAM</h1>
                <p class="text-sm text-gray-500">Sistem Operasional Pelayaran</p>
            </div>

            <div class="flex gap-3">
                <input wire:model.live="search" placeholder="Cari kapal / voyage"
                    class="rounded-xl border-gray-300 text-sm w-64">

                <select wire:model.live="period" class="rounded-xl border-gray-300 text-sm">
                    @foreach ($monthOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="flex gap-3 border-b pb-3">
            @foreach ([
        'control' => 'Pusat Kendali Operasional',
        'performance' => 'Pencapaian',
        'calendar' => 'Kalender',
    ] as $key => $label)
                <button wire:click="$set('mode','{{ $key }}')"
                    class="px-4 py-2 rounded-lg text-sm font-semibold
                        {{ $mode === $key ? 'bg-gray-900 text-white' : 'bg-gray-100' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>

        @if ($mode === 'control')

            @php
                $delayed = $rows->filter(fn($v) => $v->operational_status === 'delayed')->sortByDesc('overdue_days');

                $sailing = $rows->filter(fn($v) => $v->operational_status === 'sailing');

                $riskEta = $sailing->filter(fn($v) => $v->sailing_risk === true);

                $normalSailing = $sailing->filter(fn($v) => $v->sailing_risk === false);
            @endphp

            <div class="grid grid-cols-4 gap-4">

                <div class="bg-red-600 text-white rounded-xl p-4">
                    <div class="text-xs uppercase opacity-80">Terlambat</div>
                    <div class="text-2xl font-bold">{{ $delayed->count() }}</div>
                </div>

                <div class="bg-blue-600 text-white rounded-xl p-4">
                    <div class="text-xs uppercase opacity-80">Berlayar</div>
                    <div class="text-2xl font-bold">{{ $sailing->count() }}</div>
                </div>

                <div class="bg-orange-500 text-white rounded-xl p-4">
                    <div class="text-xs uppercase opacity-80">Risiko ETA</div>
                    <div class="text-2xl font-bold">{{ $riskEta->count() }}</div>
                </div>

                <div class="bg-gray-800 text-white rounded-xl p-4">
                    <div class="text-xs uppercase opacity-80">Total Aktif</div>
                    <div class="text-2xl font-bold">
                        {{ $delayed->count() + $sailing->count() }}
                    </div>
                </div>

            </div>

            @if ($delayed->count())
                <div class="bg-red-50 border border-red-300 rounded-2xl p-6 space-y-4">
                    <div class="font-semibold text-red-700 uppercase text-sm">
                        TERLAMBAT
                    </div>

                    @foreach ($delayed as $v)
                        @include('filament.pages.partials.voyage-card', ['v' => $v])
                    @endforeach
                </div>
            @endif

            @if ($riskEta->count())
                <div class="bg-orange-50 border border-orange-300 rounded-2xl p-6 space-y-4">
                    <div class="font-semibold text-orange-700 uppercase text-sm">
                        RISIKO ETA < 24 JAM </div>

                            @foreach ($riskEta as $v)
                                @include('filament.pages.partials.voyage-card', ['v' => $v])
                            @endforeach
                    </div>
            @endif

            @if ($normalSailing->count())
                <div class="bg-blue-50 border border-blue-300 rounded-2xl p-6 space-y-4">
                    <div class="font-semibold text-blue-700 uppercase text-sm">
                        SEDANG BERLAYAR
                    </div>

                    @foreach ($normalSailing as $v)
                        @include('filament.pages.partials.voyage-card', ['v' => $v])
                    @endforeach
                </div>
            @endif

            @if (!$delayed->count() && !$sailing->count())
                <div class="bg-white border rounded-xl p-6 text-center text-gray-500">
                    Tidak ada kapal aktif pada periode ini.
                </div>
            @endif

        @endif

        @if ($mode === 'performance')
            @include('filament.pages.partials.tam-achievments')
        @endif

        @if ($mode === 'calendar')
            @include('filament.pages.partials.tam-calendar')
        @endif

    </div>
</x-filament-panels::page>
