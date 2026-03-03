<x-filament-panels::page>
    <div class="space-y-8">

        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold">Monitoring Kapal TAM</h1>
                <p class="text-sm text-gray-500">Sistem Operasional Pelayaran</p>
            </div>

            <div class="flex gap-3">
                <input wire:model.live="search"
                    placeholder="Cari kapal / voyage"
                    class="rounded-xl border-gray-300 text-sm w-64">

                <select wire:model.live="period"
                    class="rounded-xl border-gray-300 text-sm">
                    @foreach ($monthOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="flex gap-3 border-b pb-3">
            @foreach ([
                'control' => 'Pusat Kendali Operasional',
                'dashboard' => 'Dashboard',
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
                $delayed = $rows->filter(fn($v) => $v->operational_status_enum->value === 'delayed')
                    ->sortByDesc('overdue_days');

                $sailing = $rows->filter(fn($v) => $v->operational_status_enum->value === 'sailing');

                $riskEta = $sailing->filter(fn($v) => $v->sailing_risk === true);

                $normalSailing = $sailing->filter(fn($v) => $v->sailing_risk === false);
            @endphp

            <div class="grid grid-cols-4 gap-4">

                <div class="bg-red-600 text-white rounded-xl p-4">
                    <div class="text-xs uppercase opacity-80">Terlambat</div>
                    <div class="text-2xl font-bold">
                        {{ $delayed->count() }}
                    </div>
                </div>

                <div class="bg-blue-600 text-white rounded-xl p-4">
                    <div class="text-xs uppercase opacity-80">Berlayar</div>
                    <div class="text-2xl font-bold">
                        {{ $sailing->count() }}
                    </div>
                </div>

                <div class="bg-orange-500 text-white rounded-xl p-4">
                    <div class="text-xs uppercase opacity-80">Risiko ETA</div>
                    <div class="text-2xl font-bold">
                        {{ $riskEta->count() }}
                    </div>
                </div>

                <div class="bg-gray-800 text-white rounded-xl p-4">
                    <div class="text-xs uppercase opacity-80">Total Aktif</div>
                    <div class="text-2xl font-bold">
                        {{ $delayed->count() + $sailing->count() }}
                    </div>
                </div>

            </div>

            @if ($delayed->isNotEmpty())
                <div class="bg-red-50 border border-red-300 rounded-2xl p-6 space-y-4">
                    <div class="font-semibold text-red-700 uppercase text-sm">
                        TERLAMBAT
                    </div>

                    @foreach ($delayed as $v)
                        @include('filament.pages.partials.voyage-card', ['v' => $v])
                    @endforeach
                </div>
            @endif

            @if ($riskEta->isNotEmpty())
                <div class="bg-orange-50 border border-orange-300 rounded-2xl p-6 space-y-4">
                    <div class="font-semibold text-orange-700 uppercase text-sm">
                        RISIKO ETA &lt; 24 JAM
                    </div>

                    @foreach ($riskEta as $v)
                        @include('filament.pages.partials.voyage-card', ['v' => $v])
                    @endforeach
                </div>
            @endif

            @if ($normalSailing->isNotEmpty())
                <div class="bg-blue-50 border border-blue-300 rounded-2xl p-6 space-y-4">
                    <div class="font-semibold text-blue-700 uppercase text-sm">
                        SEDANG BERLAYAR
                    </div>

                    @foreach ($normalSailing as $v)
                        @include('filament.pages.partials.voyage-card', ['v' => $v])
                    @endforeach
                </div>
            @endif

            @if ($delayed->isEmpty() && $sailing->isEmpty())
                <div class="bg-white border rounded-xl p-6 text-center text-gray-500">
                    Tidak ada kapal aktif pada periode ini.
                </div>
            @endif

        @endif


        @if ($mode === 'dashboard')

            <div class="grid grid-cols-4 gap-6">

                @foreach ([
                    'otd' => 'OTD',
                    'ota' => 'OTA',
                    'otb' => 'OTB',
                    'sla' => 'SLA',
                ] as $key => $label)

                    @php
                        $data = $achievement[$key] ?? null;
                        $percent = $data['ok_percent'] ?? 0;
                        $total = $data['total'] ?? 0;

                        $color = match (true) {
                            $percent >= 85 => 'text-green-600',
                            $percent >= 60 => 'text-orange-500',
                            default => 'text-red-600',
                        };
                    @endphp

                    <div class="bg-white rounded-2xl border p-6 shadow-sm">
                        <div class="text-xs text-gray-500 uppercase">{{ $label }}</div>
                        <div class="mt-3 text-3xl font-bold {{ $total > 0 ? $color : 'text-gray-400' }}">
                            {{ $total > 0 ? $percent . '%' : '—' }}
                        </div>
                        <div class="text-xs text-gray-500 mt-1">
                            {{ $data['ok'] ?? 0 }} / {{ $total }}
                        </div>
                    </div>

                @endforeach

            </div>

            <div class="grid grid-cols-2 gap-6">

                <div class="bg-gray-50 rounded-2xl border p-6">
                    <div class="text-xs text-gray-500 uppercase">
                        Rata-rata Keterlambatan Berangkat
                    </div>
                    <div class="text-2xl font-bold text-orange-600 mt-2">
                        {{ ($achievement['rata_rata_delay_berangkat'] ?? 0) > 0
                            ? $achievement['rata_rata_delay_berangkat'] . ' jam'
                            : '—' }}
                    </div>
                </div>

                <div class="bg-gray-50 rounded-2xl border p-6">
                    <div class="text-xs text-gray-500 uppercase">
                        Penyebab Keterlambatan Terbanyak
                    </div>
                    <div class="text-2xl font-bold text-red-600 mt-2">
                        {{ $achievement['penyebab_terbanyak'] ?? '—' }}
                    </div>
                </div>

            </div>

            @include('filament.pages.partials.tam-calendar')

        @endif

    </div>
</x-filament-panels::page>