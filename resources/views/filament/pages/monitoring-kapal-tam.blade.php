<x-filament-panels::page>
    <div class="space-y-5">

        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-xl font-bold text-gray-900 dark:text-white">Monitoring Kapal TAM</h1>
                <p class="text-sm text-gray-500 dark:text-slate-400">Sistem Operasional Pelayaran</p>
            </div>

            <div class="flex gap-3">
                <input wire:model.live="search" placeholder="Cari kapal / voyage"
                    class="rounded-lg border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-gray-900 dark:text-white text-sm w-64 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-colors duration-150">

                <select wire:model.live="period"
                    class="rounded-lg border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-gray-900 dark:text-white text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-colors duration-150">
                    @foreach ($monthOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="flex gap-1 border-b border-gray-200 dark:border-slate-800 pb-2">
            @foreach (['control' => 'Pusat Kendali Operasional', 'dashboard' => 'Dashboard'] as $key => $label)
                <button wire:click="$set('mode','{{ $key }}')"
                    class="relative px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200 focus:outline-none
                        {{ $mode === $key
                            ? 'bg-white text-slate-900 shadow-sm ring-1 ring-slate-200 dark:bg-slate-800 dark:text-white dark:ring-slate-700'
                            : 'bg-transparent text-slate-500 hover:bg-slate-100 hover:text-slate-700 dark:text-slate-400 dark:hover:bg-slate-800/60 dark:hover:text-slate-200' }}">
                    {{ $label }}
                    @if ($mode === $key)
                        <span class="absolute bottom-0 left-1/2 -translate-x-1/2 w-4 h-0.5 bg-blue-500 rounded-full"></span>
                    @endif
                </button>
            @endforeach
        </div>

        @if ($mode === 'control')
            <div wire:key="control-panel" class="animate-fade-in-up space-y-5">
            @php
                $aktif = $rows->filter(fn($v) => $v->operational_status_enum !== \App\Enums\VoyageOperationalStatus::COMPLETED);
                $tertunda = $aktif->filter(fn($v) => $v->operational_status_enum === \App\Enums\VoyageOperationalStatus::DELAYED);
                $berlayar = $aktif->filter(fn($v) => $v->operational_status_enum === \App\Enums\VoyageOperationalStatus::SAILING);
                $terjadwal = $aktif->filter(fn($v) => $v->operational_status_enum === \App\Enums\VoyageOperationalStatus::SCHEDULED);
            @endphp

            <div class="grid grid-cols-4 gap-3">
                <div class="bg-red-600 text-white rounded-lg p-4">
                    <div class="text-[11px] uppercase opacity-80 tracking-wider">Tertunda</div>
                    <div class="text-2xl font-bold mt-1">{{ $tertunda->count() }}</div>
                </div>
                <div class="bg-blue-600 text-white rounded-lg p-4">
                    <div class="text-[11px] uppercase opacity-80 tracking-wider">Berlayar</div>
                    <div class="text-2xl font-bold mt-1">{{ $berlayar->count() }}</div>
                </div>
                <div class="bg-slate-700 text-white rounded-lg p-4">
                    <div class="text-[11px] uppercase opacity-80 tracking-wider">Terjadwal</div>
                    <div class="text-2xl font-bold mt-1">{{ $terjadwal->count() }}</div>
                </div>
                <div class="bg-emerald-600 text-white rounded-lg p-4">
                    <div class="text-[11px] uppercase opacity-80 tracking-wider">Total Aktif</div>
                    <div class="text-2xl font-bold mt-1">{{ $aktif->count() }}</div>
                </div>
            </div>

            @if ($berlayar->count())
                <div>
                    <div class="text-xs font-semibold text-blue-700 dark:text-blue-400 uppercase tracking-wider mb-3">Sedang Berlayar</div>
                    @foreach ($berlayar as $v)
                        @include('filament.pages.partials.voyage-card-monitoring', ['v' => $v])
                    @endforeach
                </div>
            @endif

            @if ($tertunda->count())
                <div>
                    <div class="text-xs font-semibold text-red-700 dark:text-red-400 uppercase tracking-wider mb-3">Keberangkatan Tertunda</div>
                    @foreach ($tertunda as $v)
                        @include('filament.pages.partials.voyage-card', ['v' => $v])
                    @endforeach
                </div>
            @endif

            @if ($terjadwal->count())
                <div>
                    <div class="text-xs font-semibold text-gray-700 dark:text-slate-400 uppercase tracking-wider mb-3">Terjadwal (Belum Berangkat)</div>
                    @foreach ($terjadwal as $v)
                        @include('filament.pages.partials.voyage-card', ['v' => $v])
                    @endforeach
                </div>
            @endif

            @if (!$aktif->count())
                <div class="flex flex-col items-center justify-center py-10 bg-white dark:bg-slate-900/80 border border-gray-200 dark:border-slate-800 rounded-lg text-center text-gray-500 dark:text-slate-400">
                    <x-heroicon-o-truck class="w-8 h-8 opacity-35 mb-3" />
                    <p class="text-sm font-medium">Tidak ada pelayaran aktif pada periode ini.</p>
                </div>
            @endif
            </div>
        @endif

        {{-- MODAL MILESTONE --}}
        @if ($showMilestoneModal && $selectedMilestone)
            <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
                <div class="bg-white dark:bg-slate-900 rounded-xl w-[500px] p-5 border border-gray-200 dark:border-slate-800 dark:shadow-sm dark:shadow-black/10">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-base font-semibold text-gray-900 dark:text-white">Detail Milestone {{ strtoupper($selectedMilestone->code) }}</h2>
                        <button wire:click="$set('showMilestoneModal', false)"
                            class="text-gray-500 dark:text-slate-400 hover:text-gray-700 dark:hover:text-slate-200 transition-colors duration-150">
                            <x-heroicon-o-x-mark class="w-4 h-4" />
                        </button>
                    </div>

                    <div class="space-y-2.5 text-sm">
                        <div>
                            <div class="text-gray-500 dark:text-slate-400 text-xs">Voyage</div>
                            <div class="font-semibold text-gray-900 dark:text-white">{{ $selectedMilestone->voyage->voyage_no }}</div>
                        </div>
                        <div>
                            <div class="text-gray-500 dark:text-slate-400 text-xs">Kapal</div>
                            <div class="font-semibold text-gray-900 dark:text-white">{{ $selectedMilestone->voyage->vessel?->name }}</div>
                        </div>
                        <div>
                            <div class="text-gray-500 dark:text-slate-400 text-xs">Pelabuhan</div>
                            <div class="font-semibold text-gray-900 dark:text-white">{{ $selectedMilestone->port?->name ?? '-' }}</div>
                        </div>
                        <div>
                            <div class="text-gray-500 dark:text-slate-400 text-xs">Tanggal Milestone</div>
                            <div class="font-semibold text-gray-900 dark:text-white">{{ optional($selectedMilestone->milestone_date)->format('d M Y') }}</div>
                        </div>
                        <div>
                            <div class="text-gray-500 dark:text-slate-400 text-xs">Status Laporan</div>
                            <div class="font-semibold text-gray-900 dark:text-white capitalize">{{ $selectedMilestone->status ?? '-' }}</div>
                        </div>
                    </div>

                    <div class="border-t border-gray-200 dark:border-slate-800 pt-4 mt-4 space-y-3 text-sm">
                        <div>
                            <div class="text-gray-500 dark:text-slate-400 text-xs mb-1">Tanggal Dilaporkan</div>
                            <input type="date" wire:model="milestoneForm.actual_date"
                                class="w-full rounded-lg border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-gray-900 dark:text-white text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-colors duration-150">
                        </div>
                        <div>
                            <div class="text-gray-500 dark:text-slate-400 text-xs mb-1">Kecepatan Kapal</div>
                            <input type="number" step="0.1" wire:model="milestoneForm.speed_knots"
                                class="w-full rounded-lg border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-gray-900 dark:text-white text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-colors duration-150">
                        </div>
                        <div>
                            <div class="text-gray-500 dark:text-slate-400 text-xs mb-1">Catatan Monitoring</div>
                            <textarea wire:model="milestoneForm.note" rows="3"
                                class="w-full rounded-lg border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-gray-900 dark:text-white text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-colors duration-150"></textarea>
                        </div>
                    </div>

                    <div class="mt-5 flex justify-end gap-2">
                        <button wire:click="$set('showMilestoneModal', false)"
                            class="px-4 py-2 border border-gray-200 dark:border-slate-700 rounded-lg text-sm text-gray-700 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-800 transition-colors duration-150">Batal</button>
                        <button wire:click="saveMilestone" class="px-4 py-2 bg-gray-900 text-white rounded-lg text-sm hover:bg-gray-800 transition-colors duration-150">Simpan</button>
                    </div>
                </div>
            </div>
        @endif

        @if ($mode === 'dashboard')
            <div wire:key="dashboard-panel" class="animate-fade-in-up space-y-5">
            <div class="grid grid-cols-3 gap-4">
                <div class="bg-white dark:bg-slate-900/80 rounded-xl border border-gray-200 dark:border-slate-800 dark:shadow-sm dark:shadow-black/10 p-5">
                    <div class="text-[11px] text-gray-500 dark:text-slate-400 uppercase tracking-wider">Total Voyage</div>
                    <div class="text-2xl font-bold mt-1 text-gray-900 dark:text-white">{{ $summary['total_voyage'] ?? 0 }}</div>
                </div>
                <div class="bg-white dark:bg-slate-900/80 rounded-xl border border-gray-200 dark:border-slate-800 dark:shadow-sm dark:shadow-black/10 p-5">
                    <div class="text-[11px] text-gray-500 dark:text-slate-400 uppercase tracking-wider">Voyage Delay</div>
                    <div class="text-2xl font-bold text-red-600 dark:text-red-400 mt-1">{{ $summary['voyage_delay'] ?? 0 }}</div>
                </div>
                <div class="bg-white dark:bg-slate-900/80 rounded-xl border border-gray-200 dark:border-slate-800 dark:shadow-sm dark:shadow-black/10 p-5">
                    <div class="text-[11px] text-gray-500 dark:text-slate-400 uppercase tracking-wider">Milestone Overdue</div>
                    <div class="text-2xl font-bold text-orange-600 mt-1">{{ $summary['milestone_overdue'] ?? 0 }}</div>
                </div>
            </div>

            <div class="grid grid-cols-4 gap-4">
                @foreach (['otd' => 'OTD', 'ota' => 'OTA', 'otb' => 'OTB'] as $key => $label)
                    @php
                        $data = $achievement[$key] ?? null;
                        $percent = $data['ok_percent'] ?? 0;
                        $total = $data['total'] ?? 0;
                        $color = match (true) {
                            $percent >= 85 => 'text-green-600 dark:text-green-400',
                            $percent >= 60 => 'text-orange-500',
                            default => 'text-red-600 dark:text-red-400',
                        };
                    @endphp
                    <div class="bg-white dark:bg-slate-900/80 rounded-xl border border-gray-200 dark:border-slate-800 dark:shadow-sm dark:shadow-black/10 p-5">
                        <div class="text-[11px] text-gray-500 dark:text-slate-400 uppercase tracking-wider">{{ $label }}</div>
                        <div class="mt-2 text-2xl font-bold {{ $total > 0 ? $color : 'text-gray-400 dark:text-slate-500' }}">
                            {{ $total > 0 ? $percent . '%' : '—' }}
                        </div>
                        <div class="text-[11px] text-gray-500 dark:text-slate-400 mt-1">{{ $data['ok'] ?? 0 }} / {{ $total }}</div>
                    </div>
                @endforeach
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="bg-gray-50 dark:bg-slate-950 rounded-xl border border-gray-200 dark:border-slate-800 p-5">
                    <div class="text-[11px] text-gray-500 dark:text-slate-400 uppercase tracking-wider">Rata-rata Keterlambatan Berangkat</div>
                    <div class="text-xl font-bold text-orange-600 mt-2">
                        {{ ($achievement['rata_rata_delay_berangkat'] ?? 0) > 0 ? $achievement['rata_rata_delay_berangkat'] . ' Hari' : '—' }}
                    </div>
                </div>
                <div class="bg-gray-50 dark:bg-slate-950 rounded-xl border border-gray-200 dark:border-slate-800 p-5">
                    <div class="text-[11px] text-gray-500 dark:text-slate-400 uppercase tracking-wider">Penyebab Keterlambatan Terbanyak</div>
                    <div class="text-xl font-bold text-red-600 dark:text-red-400 mt-2">{{ $achievement['penyebab_terbanyak'] ?? '—' }}</div>
                </div>
            </div>

            @include('filament.pages.partials.tam-calendar')
            </div>
        @endif

    </div>
</x-filament-panels::page>
