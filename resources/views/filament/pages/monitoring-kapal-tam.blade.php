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
                        <option value="{{ $value }}">
                            {{ $label }}
                        </option>
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

                $aktif = $rows->filter(
                    fn($v) => $v->operational_status_enum !== \App\Enums\VoyageOperationalStatus::COMPLETED,
                );

                $tertunda = $aktif->filter(
                    fn($v) => $v->operational_status_enum === \App\Enums\VoyageOperationalStatus::DELAYED,
                );

                $berlayar = $aktif->filter(
                    fn($v) => $v->operational_status_enum === \App\Enums\VoyageOperationalStatus::SAILING,
                );

                $terjadwal = $aktif->filter(
                    fn($v) => $v->operational_status_enum === \App\Enums\VoyageOperationalStatus::SCHEDULED,
                );

            @endphp


            <div class="grid grid-cols-4 gap-4">

                <div class="bg-red-600 text-white rounded-xl p-4">
                    <div class="text-xs uppercase opacity-80">Tertunda</div>
                    <div class="text-2xl font-bold">{{ $tertunda->count() }}</div>
                </div>

                <div class="bg-blue-600 text-white rounded-xl p-4">
                    <div class="text-xs uppercase opacity-80">Berlayar</div>
                    <div class="text-2xl font-bold">{{ $berlayar->count() }}</div>
                </div>

                <div class="bg-gray-800 text-white rounded-xl p-4">
                    <div class="text-xs uppercase opacity-80">Terjadwal</div>
                    <div class="text-2xl font-bold">{{ $terjadwal->count() }}</div>
                </div>

                <div class="bg-green-600 text-white rounded-xl p-4">
                    <div class="text-xs uppercase opacity-80">Total Aktif</div>
                    <div class="text-2xl font-bold">{{ $aktif->count() }}</div>
                </div>

            </div>


            @if ($berlayar->count())

                <div class="mt-10">

                    <div class="font-semibold text-blue-700 uppercase text-sm mb-4">
                        🔵 Sedang Berlayar
                    </div>

                    @foreach ($berlayar as $v)
                        @include('filament.pages.partials.voyage-card-monitoring', ['v' => $v])
                    @endforeach

                </div>

            @endif


            @if ($tertunda->count())

                <div class="mt-10">

                    <div class="font-semibold text-red-700 uppercase text-sm mb-4">
                        🔴 Keberangkatan Tertunda
                    </div>

                    @foreach ($tertunda as $v)
                        @include('filament.pages.partials.voyage-card', ['v' => $v])
                    @endforeach

                </div>

            @endif


            @if ($terjadwal->count())

                <div class="mt-10">

                    <div class="font-semibold text-gray-700 uppercase text-sm mb-4">
                        ⚫ Terjadwal (Belum Berangkat)
                    </div>

                    @foreach ($terjadwal as $v)
                        @include('filament.pages.partials.voyage-card', ['v' => $v])
                    @endforeach

                </div>

            @endif


            @if (!$aktif->count())
                <div class="bg-white border rounded-xl p-6 text-center text-gray-500 mt-6">
                    Tidak ada pelayaran aktif pada periode ini.
                </div>
            @endif

        @endif



        @if ($showMilestoneModal && $selectedMilestone)
            <div class="fixed inset-0 bg-black/40 flex items-center justify-center z-50">

                <div class="bg-white rounded-xl shadow-xl w-[500px] p-6">

                    <div class="flex justify-between items-center mb-4">

                        <h2 class="text-lg font-semibold">
                            Detail Milestone {{ strtoupper($selectedMilestone->code) }}
                        </h2>

                        <button wire:click="$set('showMilestoneModal', false)"
                            class="text-gray-500 hover:text-gray-700">
                            ✕
                        </button>

                    </div>


                    <div class="space-y-3 text-sm">

                        <div>
                            <div class="text-gray-500">Voyage</div>
                            <div class="font-semibold">
                                {{ $selectedMilestone->voyage->voyage_no }}
                            </div>
                        </div>

                        <div>
                            <div class="text-gray-500">Kapal</div>
                            <div class="font-semibold">
                                {{ $selectedMilestone->voyage->vessel?->name }}
                            </div>
                        </div>

                        <div>
                            <div class="text-gray-500">Pelabuhan</div>
                            <div class="font-semibold">
                                {{ $selectedMilestone->port?->name ?? '-' }}
                            </div>
                        </div>

                        <div>
                            <div class="text-gray-500">Tanggal Milestone</div>
                            <div class="font-semibold">
                                {{ optional($selectedMilestone->milestone_date)->format('d M Y H:i') }}
                            </div>
                        </div>

                        <div>
                            <div class="text-gray-500">Tanggal Dilaporkan</div>
                            <div class="font-semibold">
                                {{ optional($selectedMilestone->actual_date)->format('d M Y H:i') ?? '-' }}
                            </div>
                        </div>

                        <div>
                            <div class="text-gray-500">Kecepatan Kapal</div>
                            <div class="font-semibold">
                                {{ $selectedMilestone->speed_knots ? $selectedMilestone->speed_knots . ' knots' : '-' }}
                            </div>
                        </div>

                        <div>
                            <div class="text-gray-500">Status Laporan</div>
                            <div class="font-semibold capitalize">
                                {{ $selectedMilestone->status ?? '-' }}
                            </div>
                        </div>

                        <div>
                            <div class="text-gray-500">Catatan Monitoring</div>

                            <div class="bg-gray-50 border rounded-lg p-3 mt-1">
                                {{ $selectedMilestone->note ?? 'Tidak ada catatan.' }}
                            </div>

                        </div>

                    </div>


                    <div class="mt-6 text-right">

                        <button wire:click="$set('showMilestoneModal', false)"
                            class="px-4 py-2 bg-gray-900 text-white rounded-lg text-sm">
                            Tutup
                        </button>

                    </div>

                </div>

            </div>
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
