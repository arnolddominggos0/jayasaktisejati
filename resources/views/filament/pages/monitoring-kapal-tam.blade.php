<x-filament-panels::page>
    <div class="space-y-8">

        <div class="rounded-2xl bg-white p-6 shadow-sm border border-gray-100">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-semibold">
                        Monitoring Kapal TAM
                    </h1>
                    <p class="text-sm text-gray-500">
                        Monitoring status operasional dan SLA per periode
                    </p>
                </div>

                <div class="flex gap-3 items-center">
                    <input type="text"
                        wire:model.live="search"
                        placeholder="Cari kapal / voyage..."
                        class="rounded-xl border-gray-300 text-sm w-64">

                    <select wire:model.live="period"
                        class="rounded-xl border-gray-300 text-sm">
                        @foreach ($monthOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-5 gap-4">
            <div class="bg-white p-4 rounded-xl border">
                <div class="text-xs text-gray-500">Total Voyage</div>
                <div class="text-2xl font-bold">{{ $summary['total'] }}</div>
            </div>

            <div class="bg-white p-4 rounded-xl border">
                <div class="text-xs text-gray-500">Delay</div>
                <div class="text-2xl font-bold text-red-600">{{ $summary['delay'] }}</div>
            </div>

            <div class="bg-white p-4 rounded-xl border">
                <div class="text-xs text-gray-500">SLA Tercapai</div>
                <div class="text-2xl font-bold text-green-600">{{ $summary['sla_ok'] }}</div>
            </div>

            <div class="bg-white p-4 rounded-xl border">
                <div class="text-xs text-gray-500">Belum ATD</div>
                <div class="text-2xl font-bold text-orange-600">{{ $summary['no_atd'] }}</div>
            </div>

            <div class="bg-white p-4 rounded-xl border">
                <div class="text-xs text-gray-500">Belum ATA</div>
                <div class="text-2xl font-bold text-orange-600">{{ $summary['no_ata'] }}</div>
            </div>
        </div>

        <div class="flex gap-3 border-b pb-3">
            <button wire:click="$set('filter','all')" class="px-4 py-2 rounded-lg text-sm font-semibold {{ $filter==='all' ? 'bg-gray-900 text-white' : 'bg-gray-100' }}">Semua</button>
            <button wire:click="$set('filter','delay')" class="px-4 py-2 rounded-lg text-sm font-semibold {{ $filter==='delay' ? 'bg-red-600 text-white' : 'bg-red-100 text-red-600' }}">Terlambat</button>
            <button wire:click="$set('filter','ongoing')" class="px-4 py-2 rounded-lg text-sm font-semibold {{ $filter==='ongoing' ? 'bg-blue-600 text-white' : 'bg-blue-100 text-blue-600' }}">Sedang Berlayar</button>
            <button wire:click="$set('filter','belum_update')" class="px-4 py-2 rounded-lg text-sm font-semibold {{ $filter==='belum_update' ? 'bg-orange-600 text-white' : 'bg-orange-100 text-orange-600' }}">Belum Update</button>
        </div>

        <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm border-collapse">

                    <thead class="bg-gray-50 text-xs uppercase text-gray-600">
                        <tr>
                            <th class="px-4 py-3 text-left">Kapal</th>
                            <th class="px-4 py-3 text-left">Voyage</th>
                            <th class="px-4 py-3 text-left">Rute</th>
                            <th class="px-4 py-3 text-center">Status</th>
                            <th class="px-4 py-3 text-center">ETD</th>
                            <th class="px-4 py-3 text-center">ETA</th>
                            <th class="px-4 py-3 text-center">Transit SLA</th>
                            <th class="px-4 py-3 text-left">Reason</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($rows as $v)

                            @php
                                $badgeClass = match($v->operational_status) {
                                    'scheduled' => 'bg-gray-100 text-gray-700',
                                    'sailing'   => 'bg-blue-100 text-blue-700',
                                    'delayed'   => 'bg-red-100 text-red-700',
                                    'completed' => 'bg-green-100 text-green-700',
                                    default     => 'bg-gray-100 text-gray-700'
                                };
                            @endphp

                            <tr class="border-t
                                {{ $v->operational_status === 'delayed' ? 'bg-red-50 border-l-4 border-red-500' : '' }}
                                {{ $v->operational_status === 'sailing' ? 'bg-blue-50 border-l-4 border-blue-400' : '' }}">

                                <td class="px-4 py-3 font-medium">
                                    {{ $v->vessel?->name ?? '-' }}
                                </td>

                                <td class="px-4 py-3">
                                    {{ $v->voyage_no }}
                                </td>

                                <td class="px-4 py-3">
                                    {{ $v->pol?->code }} → {{ $v->pod?->code }}
                                </td>

                                <td class="px-4 py-3 text-center">
                                    <span class="px-2 py-1 text-xs rounded font-semibold {{ $badgeClass }}">
                                        {{ $v->operational_status_label }}
                                    </span>

                                    @if($v->overdue_days)
                                        <div class="text-xs font-semibold mt-1
                                            {{ $v->overdue_days >= 7 ? 'text-red-700' : 'text-orange-600' }}">
                                            Overdue {{ $v->overdue_days }} hari
                                        </div>
                                    @endif
                                </td>

                                <td class="px-4 py-3 text-center">
                                    {{ optional($v->etd)->format('d M H:i') ?? '-' }}
                                </td>

                                <td class="px-4 py-3 text-center">
                                    {{ optional($v->eta)->format('d M H:i') ?? '-' }}
                                </td>

                                <td class="px-4 py-3 text-center">
                                    @if($v->sailingSla)
                                        <span class="px-2 py-1 text-xs rounded font-semibold {{ $v->sailingSla->status->color() }}">
                                            {{ $v->sailingSla->status->label() }}
                                        </span>
                                    @else
                                        -
                                    @endif
                                </td>

                                <td class="px-4 py-3 text-xs">
                                    {{ $v->delay_reason?->label() ?? '-' }}
                                </td>

                            </tr>

                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-6 text-gray-400">
                                    Tidak ada data
                                </td>
                            </tr>
                        @endforelse
                    </tbody>

                </table>
            </div>
        </div>

    </div>
</x-filament-panels::page>