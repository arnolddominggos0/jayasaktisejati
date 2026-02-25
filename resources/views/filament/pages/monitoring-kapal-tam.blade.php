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
                'performance' => 'Pencapaian',
                'calendar' => 'Kalender',
            ] as $key => $label)
                <button
                    wire:click="$set('mode','{{ $key }}')"
                    class="px-4 py-2 rounded-lg text-sm font-semibold
                    {{ $mode === $key ? 'bg-gray-900 text-white' : 'bg-gray-100' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>

        @if ($mode === 'control')

            <div class="grid grid-cols-6 gap-4">
                <div class="bg-white rounded-xl border p-4">
                    <div class="text-xs text-gray-500 uppercase">Total</div>
                    <div class="text-2xl font-bold">{{ $summary['total'] ?? 0 }}</div>
                </div>

                <div class="bg-red-50 rounded-xl border border-red-200 p-4">
                    <div class="text-xs text-red-600 uppercase">Terlambat</div>
                    <div class="text-2xl font-bold text-red-700">{{ $summary['delayed'] ?? 0 }}</div>
                </div>

                <div class="bg-blue-50 rounded-xl border border-blue-200 p-4">
                    <div class="text-xs text-blue-600 uppercase">Berlayar</div>
                    <div class="text-2xl font-bold text-blue-700">{{ $summary['sailing'] ?? 0 }}</div>
                </div>

                <div class="bg-green-50 rounded-xl border border-green-200 p-4">
                    <div class="text-xs text-green-600 uppercase">Selesai</div>
                    <div class="text-2xl font-bold text-green-700">{{ $summary['completed'] ?? 0 }}</div>
                </div>

                <div class="bg-red-50 rounded-xl border border-red-200 p-4">
                    <div class="text-xs text-red-600 uppercase">SLA Gagal</div>
                    <div class="text-2xl font-bold text-red-700">{{ $summary['sla_fail'] ?? 0 }}</div>
                </div>

                <div class="bg-orange-50 rounded-xl border border-orange-200 p-4">
                    <div class="text-xs text-orange-600 uppercase">Belum ATA</div>
                    <div class="text-2xl font-bold text-orange-700">{{ $summary['no_ata'] ?? 0 }}</div>
                </div>
            </div>

            <div class="space-y-6 mt-6">

                @forelse($rows as $v)

                    @php
                        $milestones = collect($v->milestones ?? []);
                    @endphp

                    <div class="bg-white rounded-xl border p-5 shadow-sm">

                        <div class="flex justify-between items-start">
                            <div>
                                <div class="font-semibold text-lg">
                                    {{ $v->vessel?->name }} — {{ $v->voyage_no }}
                                </div>
                                <div class="text-sm text-gray-500 mt-1">
                                    {{ $v->pol?->code }} → {{ $v->pod?->code }}
                                </div>
                            </div>

                            <div class="flex gap-2">
                                @if($v->is_delayed)
                                    <span class="px-3 py-1 text-xs rounded bg-red-600 text-white">
                                        TERLAMBAT
                                    </span>
                                @elseif($v->ata_at)
                                    <span class="px-3 py-1 text-xs rounded bg-green-600 text-white">
                                        SELESAI
                                    </span>
                                @elseif($v->atd_at)
                                    <span class="px-3 py-1 text-xs rounded bg-blue-600 text-white">
                                        BERLAYAR
                                    </span>
                                @else
                                    <span class="px-3 py-1 text-xs rounded bg-gray-600 text-white">
                                        TERJADWAL
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="mt-4 grid grid-cols-4 gap-6 text-sm">
                            <div>
                                <div class="text-gray-500">ETB</div>
                                <div class="font-medium">{{ optional($v->etb)->format('d M H:i') ?? '-' }}</div>
                            </div>
                            <div>
                                <div class="text-gray-500">ETD</div>
                                <div class="font-medium">{{ optional($v->etd)->format('d M H:i') ?? '-' }}</div>
                            </div>
                            <div>
                                <div class="text-gray-500">ETA</div>
                                <div class="font-medium">{{ optional($v->eta)->format('d M H:i') ?? '-' }}</div>
                            </div>
                            <div>
                                <div class="text-gray-500">ATA</div>
                                <div class="font-medium">{{ optional($v->ata_at)->format('d M H:i') ?? '-' }}</div>
                            </div>
                        </div>

                        @if($milestones->isNotEmpty())
                            <div class="mt-5 border-t pt-4 space-y-3 text-xs">

                                <div class="font-semibold text-gray-600 uppercase">
                                    Monitoring Transit (H+)
                                </div>

                                @foreach($milestones->sortBy('milestone_date') as $ms)
                                    <div class="bg-slate-50 border rounded px-3 py-2">
                                        <div class="font-medium">
                                            {{ strtoupper($ms->code) }}
                                            — {{ optional($ms->milestone_date)->format('d M Y') }}
                                        </div>

                                        @if($ms->position)
                                            <div class="text-gray-600">
                                                Posisi: {{ $ms->position }}
                                            </div>
                                        @endif

                                        @if($ms->speed_knots)
                                            <div class="text-gray-600">
                                                Kecepatan: {{ $ms->speed_knots }} Knots
                                            </div>
                                        @endif

                                        @if($ms->note)
                                            <div class="text-gray-700">
                                                {{ $ms->note }}
                                            </div>
                                        @endif
                                    </div>
                                @endforeach

                            </div>
                        @endif

                    </div>

                @empty
                    <div class="bg-white border rounded-xl p-6 text-center text-gray-500">
                        Tidak ada data voyage untuk periode ini.
                    </div>
                @endforelse

            </div>

        @endif

        @if ($mode === 'performance')
            @include('filament.pages.partials.tam-achievments')
        @endif

        @if ($mode === 'calendar')
            @include('filament.pages.partials.tam-calendar')
        @endif

    </div>
</x-filament-panels::page>