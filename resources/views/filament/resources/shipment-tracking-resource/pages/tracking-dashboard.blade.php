{{-- resources/views/filament/resources/shipment-tracking-resource/pages/tracking-dashboard.blade.php --}}
<div class="space-y-6">

    {{-- Header: search + actions --}}
    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div class="text-xl font-semibold">Pelacakan</div>

        <div class="flex items-center gap-2">
            <form method="GET" class="flex items-center gap-2">
                <x-filament::input.wrapper class="w-72">
                    <x-filament::input
                        name="q"
                        value="{{ request('q') }}"
                        placeholder="Cari kode / customer / tujuan..."
                    />
                </x-filament::input.wrapper>

                <x-filament::button type="submit" icon="heroicon-m-magnifying-glass">Cari</x-filament::button>
            </form>

            <x-filament::button
                tag="a"
                href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}"
                icon="heroicon-m-arrow-down-tray"
                color="gray"
            >Export</x-filament::button>
        </div>
    </div>

    {{-- KPI cards --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-filament::card>
            <div class="text-sm text-gray-500">AKTIF</div>
            <div class="mt-1 text-3xl font-semibold">{{ number_format($kpis['aktif'] ?? 0) }}</div>
            <div class="mt-2 text-xs text-gray-400">Pengiriman berjalan</div>
        </x-filament::card>

        <x-filament::card>
            <div class="text-sm text-gray-500">IN TRANSIT</div>
            <div class="mt-1 text-3xl font-semibold">{{ number_format($kpis['in_transit'] ?? 0) }}</div>
            <div class="mt-2 text-xs text-gray-400">Darat & Laut</div>
        </x-filament::card>

        <x-filament::card>
            <div class="text-sm text-gray-500">DOKUMEN PENDING</div>
            <div class="mt-1 text-3xl font-semibold">{{ number_format($kpis['pending'] ?? 0) }}</div>
            <div class="mt-2 text-xs text-gray-400">Butuh verifikasi</div>
        </x-filament::card>

        <x-filament::card>
            <div class="text-sm text-gray-500">TERLAMBAT</div>
            <div class="mt-1 text-3xl font-semibold text-danger-600">{{ number_format($kpis['late'] ?? 0) }}</div>
            <div class="mt-2 text-xs text-gray-400">Perlu perhatian</div>
        </x-filament::card>
    </div>

    {{-- Tabs filter --}}
    @php
        $current = request('tab', 'semua');
        $tabs = [
            'semua' => 'Semua',
            'laut' => 'Laut',
            'darat' => 'Darat',
            'tertunda' => 'Tertunda',
        ];
    @endphp
    <div class="flex items-center gap-2">
        @foreach ($tabs as $key => $label)
            <a
                href="{{ request()->fullUrlWithQuery(['tab' => $key, 'page' => null]) }}"
                @class([
                    'rounded-full px-3 py-1 text-sm border',
                    'bg-primary-600 text-white border-transparent' => $current === $key,
                    'text-gray-600 border-gray-200 hover:bg-gray-50' => $current !== $key,
                ])
            >{{ $label }}</a>
        @endforeach
    </div>

    <div class="grid grid-cols-12 gap-4">
        {{-- Daftar Pelacakan Pengiriman --}}
        <div class="col-span-12 lg:col-span-8">
            <x-filament::section>
                <x-slot name="heading">Daftar Pelacakan Pengiriman</x-slot>

                <div class="overflow-hidden rounded-xl border border-gray-200">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50/60">
                            <tr class="text-left text-xs font-semibold text-gray-600">
                                <th class="px-4 py-3">KODE</th>
                                <th class="px-4 py-3">Customer</th>
                                <th class="px-4 py-3">Rute</th>
                                <th class="px-4 py-3">Moda</th>
                                <th class="px-4 py-3">Progres</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">ETA</th>
                                <th class="px-4 py-3">Update</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse ($shipments as $s)
                                @php
                                    // helper minimal; silakan ganti dengan method di model
                                    $progressPct = (int)($s->progress_percent ?? 0);
                                    $etaColor = 'text-success-600';
                                    if (!$s->eta)         $etaColor = 'text-gray-400';
                                    elseif (\Illuminate\Support\Carbon::parse($s->eta)->isPast()) $etaColor = 'text-danger-600';
                                    elseif (\Illuminate\Support\Carbon::parse($s->eta)->diffInHours(now()) <= 48) $etaColor = 'text-warning-600';
                                    $mode = is_string($s->mode) ? strtoupper($s->mode) : (method_exists($s->mode ?? null,'label') ? $s->mode->label() : '—');
                                @endphp
                                <tr class="text-sm">
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center rounded-md bg-gray-100 px-2 py-0.5 font-mono text-xs">{{ $s->code }}</span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="font-medium">{{ $s->customer->name ?? '-' }}</div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="text-gray-700">{{ $s->originCity->name ?? '-' }} → {{ $s->destinationCity->name ?? '-' }}</div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span @class([
                                            'inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium',
                                            'bg-primary-100 text-primary-700' => $mode === 'LAUT',
                                            'bg-warning-100 text-warning-700' => $mode === 'DARAT',
                                            'bg-gray-100 text-gray-700' => !in_array($mode, ['LAUT','DARAT']),
                                        ])>{{ $mode }}</span>
                                    </td>
                                    <td class="px-4 py-3 w-44">
                                        <div class="flex items-center gap-2">
                                            <div class="h-2 w-full overflow-hidden rounded-full bg-gray-100">
                                                <div class="h-2 rounded-full bg-primary-500" style="width: {{ $progressPct }}%"></div>
                                            </div>
                                            <span class="text-xs text-gray-600 w-10 text-right">{{ $progressPct }}%</span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center rounded-md bg-blue-100 px-2 py-0.5 text-xs text-blue-700">
                                            {{ is_string($s->status) ? $s->status : ($s->status?->label() ?? '-') }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="text-sm {{ $etaColor }}">
                                            {{ $s->eta ? \Illuminate\Support\Carbon::parse($s->eta)->format('d M') : '—' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="text-xs text-gray-600">{{ $s->tracks_count ?? 0 }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <x-filament::button
                                                tag="a"
                                                color="gray"
                                                size="xs"
                                                icon="heroicon-m-eye"
                                                href="{{ route('filament.admin.resources.shipments.edit', $s) }}"
                                            >Lihat</x-filament::button>

                                            <x-filament::button
                                                tag="a"
                                                size="xs"
                                                icon="heroicon-m-plus-circle"
                                                href="{{ route('filament.admin.resources.shipment-tracking.create', ['shipment_id' => $s->id]) }}"
                                            >Update</x-filament::button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="px-4 py-8 text-center text-sm text-gray-500">
                                        Tidak ada data untuk filter saat ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Pagination sederhana jika kamu kirim paginator --}}
                @if(method_exists($shipments, 'links'))
                    <div class="mt-3">{{ $shipments->withQueryString()->links() }}</div>
                @endif
            </x-filament::section>
        </div>

        {{-- Aktivitas Terbaru --}}
        <div class="col-span-12 lg:col-span-4">
            <x-filament::section>
                <x-slot name="heading">Aktivitas Terbaru</x-slot>

                <div class="divide-y divide-gray-100">
                    @forelse ($recentTracks as $t)
                        <div class="py-3 flex items-start justify-between">
                            <div class="space-y-0.5">
                                <div class="text-sm">
                                    <span class="font-semibold">{{ $t->shipment->code ?? '-' }}</span>
                                    — <span class="text-gray-700">
                                        @if($t->status) {{ is_string($t->status) ? $t->status : ($t->status?->label() ?? '') }} @endif
                                        @if($t->checkpoint) • {{ $t->checkpoint }} @endif
                                    </span>
                                </div>
                                @if($t->location)
                                    <div class="text-xs text-gray-500">{{ $t->location }}</div>
                                @endif
                            </div>
                            <div class="text-xs text-gray-400">{{ \Illuminate\Support\Carbon::parse($t->tracked_at)->diffForHumans() }}</div>
                        </div>
                    @empty
                        <div class="py-6 text-center text-sm text-gray-500">Belum ada aktivitas.</div>
                    @endforelse
                </div>
            </x-filament::section>
        </div>
    </div>
</div>
