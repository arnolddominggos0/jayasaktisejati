<x-filament-panels::page>

    {{-- ── FILTER BAR ─────────────────────────────────────────────────────── --}}
    <div class="mb-4 p-4 bg-white rounded-xl shadow-sm border border-gray-100 dark:bg-gray-800 dark:border-gray-700">
        <div class="flex flex-wrap gap-3 items-end">

            {{-- Period --}}
            <div class="flex flex-col gap-1 min-w-[130px]">
                <label class="text-xs font-medium text-gray-500 dark:text-gray-400">Periode</label>
                <select wire:model.live="period" wire:change="applyFilters"
                        class="fi-select-input text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2 focus:ring-2 focus:ring-primary-500">
                    <option value="this_month">Bulan ini</option>
                    <option value="this_year">Tahun ini</option>
                    <option value="by_month">Per bulan</option>
                </select>
            </div>

            @if ($period === 'by_month')
            <div class="flex flex-col gap-1 min-w-[150px]">
                <label class="text-xs font-medium text-gray-500 dark:text-gray-400">Bulan</label>
                <select wire:model.live="periodMonth" wire:change="applyFilters"
                        class="fi-select-input text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2 focus:ring-2 focus:ring-primary-500">
                    @foreach ($this->getMonthOptions() as $val => $label)
                        <option value="{{ $val }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            @endif

            {{-- Voyage search --}}
            <div class="flex flex-col gap-1 flex-1 min-w-[200px]">
                <label class="text-xs font-medium text-gray-500 dark:text-gray-400">Cari Voyage</label>
                <input type="text" wire:model.live.debounce.400ms="voyageSearch"
                       wire:keydown.enter="applyFilters"
                       placeholder="Nama kapal / voyage no..."
                       class="text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2 focus:ring-2 focus:ring-primary-500" />
            </div>

            <button wire:click="applyFilters"
                    class="inline-flex items-center gap-1 px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium rounded-lg transition">
                <x-heroicon-o-magnifying-glass class="w-4 h-4" />
                Tampilkan
            </button>

        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    {{-- VIEW: VOYAGE LIST                                                       --}}
    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    @if ($currentView === 'list')

    <div class="flex justify-between items-center mb-3">
        <h2 class="text-base font-semibold text-gray-700 dark:text-gray-200">
            Ringkasan Voyage
            <span class="text-xs font-normal text-gray-400 ml-1">({{ count($voyageSummaries) }} voyage)</span>
        </h2>
        <button wire:click="exportVoyageList"
                class="inline-flex items-center gap-1 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-medium rounded-lg transition">
            <x-heroicon-o-arrow-down-tray class="w-4 h-4" />
            Export Excel
        </button>
    </div>

    @php
        $thresholds = config('jss_kpi.manado.thresholds', [
            'dwelling_days' => 6,
            'sailing_days'  => 10,
            'dooring_days'  => 3,
        ]);
        function evStatusBadge(string $view, $avg, $limit): string {
            if ($avg === null) return '<span class="text-gray-400 text-xs">–</span>';
            $ok = (float)$avg <= (float)$limit;
            $color = $ok ? 'text-emerald-700 bg-emerald-50 dark:bg-emerald-900/30 dark:text-emerald-400'
                         : 'text-red-700 bg-red-50 dark:bg-red-900/30 dark:text-red-400';
            return '<span class="px-1.5 py-0.5 rounded text-xs font-medium ' . $color . '">' . number_format((float)$avg, 2) . '</span>';
        }
    @endphp

    <div class="overflow-x-auto rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
        <table class="w-full text-sm text-left text-gray-700 dark:text-gray-300">
            <thead class="text-xs uppercase bg-gray-50 dark:bg-gray-800 text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700">
                <tr>
                    <th class="px-4 py-3 cursor-pointer select-none hover:text-primary-600" wire:click="setSort('voyage')">
                        Voyage
                        @if($sortBy === 'voyage') <span class="ml-0.5">{{ $sortDir === 'asc' ? '↑' : '↓' }}</span> @endif
                    </th>
                    <th class="px-3 py-3">Bulan</th>
                    <th class="px-3 py-3 text-center cursor-pointer hover:text-primary-600" wire:click="setSort('qty_unit')">
                        Qty Unit
                        @if($sortBy === 'qty_unit') <span>{{ $sortDir === 'asc' ? '↑' : '↓' }}</span> @endif
                    </th>
                    <th class="px-3 py-3 text-center cursor-pointer hover:text-primary-600" wire:click="setSort('avg_dwelling')">
                        Avg Dwelling
                        @if($sortBy === 'avg_dwelling') <span>{{ $sortDir === 'asc' ? '↑' : '↓' }}</span> @endif
                        <div class="text-[10px] font-normal normal-case text-gray-400">Target ≤ {{ $thresholds['dwelling_days'] ?? 6 }}h</div>
                    </th>
                    <th class="px-3 py-3 text-center cursor-pointer hover:text-primary-600" wire:click="setSort('avg_sailing')">
                        Avg Sailing
                        @if($sortBy === 'avg_sailing') <span>{{ $sortDir === 'asc' ? '↑' : '↓' }}</span> @endif
                        <div class="text-[10px] font-normal normal-case text-gray-400">Target ≤ {{ $thresholds['sailing_days'] ?? 10 }}h</div>
                    </th>
                    <th class="px-3 py-3 text-center cursor-pointer hover:text-primary-600" wire:click="setSort('avg_dooring')">
                        Avg Dooring
                        @if($sortBy === 'avg_dooring') <span>{{ $sortDir === 'asc' ? '↑' : '↓' }}</span> @endif
                        <div class="text-[10px] font-normal normal-case text-gray-400">Target ≤ {{ $thresholds['dooring_days'] ?? 3 }}h</div>
                    </th>
                    <th class="px-3 py-3 text-center cursor-pointer hover:text-primary-600" wire:click="setSort('avg_lt')">
                        Avg LT
                        @if($sortBy === 'avg_lt') <span>{{ $sortDir === 'asc' ? '↑' : '↓' }}</span> @endif
                    </th>
                    <th class="px-3 py-3 text-center cursor-pointer hover:text-primary-600" wire:click="setSort('ok_count')">
                        OK
                        @if($sortBy === 'ok_count') <span>{{ $sortDir === 'asc' ? '↑' : '↓' }}</span> @endif
                    </th>
                    <th class="px-3 py-3 text-center cursor-pointer hover:text-primary-600" wire:click="setSort('ng_count')">
                        NG
                        @if($sortBy === 'ng_count') <span>{{ $sortDir === 'asc' ? '↑' : '↓' }}</span> @endif
                    </th>
                    <th class="px-3 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse ($voyageSummaries as $row)
                <tr class="bg-white dark:bg-gray-900 hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                    <td class="px-4 py-3">
                        <div class="font-semibold text-gray-900 dark:text-white">{{ $row['vessel_name'] }}</div>
                        <div class="text-xs text-gray-400">{{ $row['voyage_no'] }}</div>
                    </td>
                    <td class="px-3 py-3 text-xs text-gray-500">{{ $row['period_label'] }}</td>
                    <td class="px-3 py-3 text-center font-medium">{{ $row['qty_unit'] }}</td>
                    <td class="px-3 py-3 text-center">
                        {!! evStatusBadge('dw', $row['avg_dwelling'], $thresholds['dwelling_days'] ?? 6) !!}
                    </td>
                    <td class="px-3 py-3 text-center">
                        {!! evStatusBadge('sa', $row['avg_sailing'], $thresholds['sailing_days'] ?? 10) !!}
                    </td>
                    <td class="px-3 py-3 text-center">
                        {!! evStatusBadge('do', $row['avg_dooring'], $thresholds['dooring_days'] ?? 3) !!}
                    </td>
                    <td class="px-3 py-3 text-center">
                        @if($row['avg_lt'] !== null)
                            <span class="font-semibold {{ $row['avg_lt'] <= ($thresholds['total_days']['normal'] ?? 19) ? 'text-emerald-600' : 'text-red-600' }}">
                                {{ number_format($row['avg_lt'], 2) }}
                            </span>
                        @else
                            <span class="text-gray-400 text-xs">–</span>
                        @endif
                    </td>
                    <td class="px-3 py-3 text-center">
                        @if($row['ok_count'] > 0)
                            <span class="px-2 py-0.5 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 rounded text-xs font-semibold">
                                {{ $row['ok_count'] }}
                            </span>
                        @else
                            <span class="text-gray-400 text-xs">0</span>
                        @endif
                    </td>
                    <td class="px-3 py-3 text-center">
                        @if($row['ng_count'] > 0)
                            <span class="px-2 py-0.5 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 rounded text-xs font-semibold">
                                {{ $row['ng_count'] }}
                            </span>
                        @else
                            <span class="text-gray-400 text-xs">0</span>
                        @endif
                    </td>
                    <td class="px-3 py-3 text-right">
                        @if($row['voyage_id'])
                        <button wire:click="openVoyage({{ $row['voyage_id'] }})"
                                class="inline-flex items-center gap-1 text-xs text-primary-600 hover:text-primary-800 font-medium">
                            Detail <x-heroicon-o-chevron-right class="w-3.5 h-3.5" />
                        </button>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="10" class="px-4 py-10 text-center text-gray-400">
                        <x-heroicon-o-inbox class="w-10 h-10 mx-auto mb-2 text-gray-300" />
                        Tidak ada data voyage pada periode ini.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @endif {{-- end list view --}}


    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    {{-- VIEW: VOYAGE DETAIL                                                      --}}
    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    @if ($currentView === 'voyage_detail')

    {{-- Breadcrumb --}}
    <nav class="mb-4 flex items-center gap-1 text-sm text-gray-500">
        <button wire:click="backToList" class="hover:text-primary-600 transition">Evaluasi Voyage</button>
        <x-heroicon-o-chevron-right class="w-4 h-4 text-gray-300" />
        <span class="font-semibold text-gray-800 dark:text-white">{{ $voyageInfo['label'] ?? '–' }}</span>
    </nav>

    {{-- Voyage header card --}}
    <div class="mb-4 p-4 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
        <div class="flex flex-wrap justify-between items-start gap-4">
            <div class="flex-1 min-w-0">
                <h2 class="text-lg font-bold text-gray-900 dark:text-white">{{ $voyageInfo['label'] ?? '–' }}</h2>
                <div class="flex flex-wrap gap-4 mt-1 text-sm text-gray-500 dark:text-gray-400">
                    @if(!empty($voyageInfo['period']))
                        <span>{{ $voyageInfo['period'] }}</span>
                    @endif
                    @if(!empty($voyageInfo['etd']))
                        <span>ETD: {{ $voyageInfo['etd'] }}</span>
                    @endif
                    @if(!empty($voyageInfo['ata']))
                        <span>ATA: {{ $voyageInfo['ata'] }}</span>
                    @endif
                    <span class="font-medium text-gray-700 dark:text-gray-300">{{ count($voyageUnits) }} unit</span>
                </div>

                {{-- Cargo summary (read-only) --}}
                @php
                    $cPlan     = $voyageInfo['cargo_plan']     ?? null;
                    $cActual   = $voyageInfo['cargo_actual']   ?? null;
                    $cVariance = $voyageInfo['cargo_variance'] ?? null;
                @endphp
                @if ($cPlan !== null || $cActual !== null)
                    <div class="mt-3 flex flex-wrap gap-4 text-xs">
                        @if ($cPlan !== null)
                            <div class="flex items-center gap-1 text-gray-500">
                                <span class="uppercase tracking-wide font-semibold">Plan</span>
                                <span class="font-semibold text-gray-700">{{ number_format($cPlan) }} unit</span>
                            </div>
                        @endif
                        @if ($cActual !== null)
                            <div class="flex items-center gap-1 text-gray-500">
                                <span class="uppercase tracking-wide font-semibold">Aktual</span>
                                <span class="font-semibold text-gray-700">{{ number_format($cActual) }} unit</span>
                            </div>
                        @endif
                        @if ($cVariance !== null)
                            <div class="flex items-center gap-1">
                                <span class="uppercase tracking-wide font-semibold text-gray-500">Variance</span>
                                <span class="font-semibold {{ $cVariance < 0 ? 'text-red-600' : ($cVariance > 0 ? 'text-green-600' : 'text-gray-500') }}">
                                    {{ $cVariance >= 0 ? '+' : '' }}{{ number_format($cVariance) }}
                                </span>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('voyage.quick-report', ['voyageId' => $selectedVoyageId]) }}"
                   target="_blank"
                   class="inline-flex items-center gap-1 px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium rounded-lg transition">
                    <x-heroicon-o-document-text class="w-4 h-4" />
                    Quick Report PDF
                </a>
                <button wire:click="exportVoyageDetail"
                        class="inline-flex items-center gap-1 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-medium rounded-lg transition">
                    <x-heroicon-o-arrow-down-tray class="w-4 h-4" />
                    Export Excel
                </button>
            </div>
        </div>
    </div>

    {{-- Unit search + filter --}}
    <div class="mb-3 flex flex-wrap gap-2 items-end">
        <input type="text" wire:model.live.debounce.400ms="unitSearch"
               wire:keydown.enter="applyUnitFilters"
               placeholder="Cari No Rangka / No Mesin..."
               class="text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2 min-w-[220px] focus:ring-2 focus:ring-primary-500" />

        <select wire:model.live="statusFilter" wire:change="applyUnitFilters"
                class="fi-select-input text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2 focus:ring-2 focus:ring-primary-500">
            <option value="">Semua Status</option>
            <option value="OK">OK</option>
            <option value="NG">NG</option>
        </select>

        <button wire:click="applyUnitFilters"
                class="inline-flex items-center gap-1 px-3 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 text-sm rounded-lg transition">
            <x-heroicon-o-funnel class="w-4 h-4" />
            Filter
        </button>
    </div>

    @php
        $thresholds = config('jss_kpi.manado.thresholds', [
            'dwelling_days' => 6,
            'sailing_days'  => 10,
            'dooring_days'  => 3,
        ]);
        $paginatedUnits = $this->getPaginatedUnits();
        $totalPages = $this->getTotalUnitPages();

        function stBadge(string $st): string {
            return match($st) {
                'OK'      => '<span class="px-1.5 py-0.5 rounded text-xs font-semibold bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400">OK</span>',
                'LATE'    => '<span class="px-1.5 py-0.5 rounded text-xs font-semibold bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400">NG</span>',
                default   => '<span class="text-gray-400 text-xs">–</span>',
            };
        }
    @endphp

    <div class="overflow-x-auto rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
        <table class="w-full text-sm text-left text-gray-700 dark:text-gray-300">
            <thead class="text-xs uppercase bg-gray-50 dark:bg-gray-800 text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700">
                <tr>
                    <th class="px-3 py-3">#</th>
                    <th class="px-4 py-3">No Rangka</th>
                    <th class="px-4 py-3">No Mesin</th>
                    <th class="px-3 py-3 text-center">
                        Dwelling
                        <div class="text-[10px] font-normal normal-case text-gray-400">≤ {{ $thresholds['dwelling_days'] ?? 6 }}h</div>
                    </th>
                    <th class="px-2 py-3 text-center">St</th>
                    <th class="px-3 py-3 text-center">
                        Sailing
                        <div class="text-[10px] font-normal normal-case text-gray-400">≤ {{ $thresholds['sailing_days'] ?? 10 }}h</div>
                    </th>
                    <th class="px-2 py-3 text-center">St</th>
                    <th class="px-3 py-3 text-center">
                        Dooring
                        <div class="text-[10px] font-normal normal-case text-gray-400">≤ {{ $thresholds['dooring_days'] ?? 3 }}h</div>
                    </th>
                    <th class="px-2 py-3 text-center">St</th>
                    <th class="px-3 py-3 text-center">LT Total</th>
                    <th class="px-2 py-3 text-center">Status</th>
                    <th class="px-3 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse ($paginatedUnits as $idx => $unit)
                <tr class="bg-white dark:bg-gray-900 hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                    <td class="px-3 py-2.5 text-xs text-gray-400">
                        {{ ($unitPage - 1) * $unitPerPage + $idx + 1 }}
                    </td>
                    <td class="px-4 py-2.5 font-mono text-xs text-gray-900 dark:text-white">
                        {{ $unit['chassis_no'] }}
                    </td>
                    <td class="px-4 py-2.5 font-mono text-xs text-gray-600 dark:text-gray-400">
                        {{ $unit['engine_no'] }}
                    </td>
                    <td class="px-3 py-2.5 text-center font-medium">
                        {{ $unit['dwelling'] ?? '–' }}
                    </td>
                    <td class="px-2 py-2.5 text-center">
                        {!! stBadge($unit['dwelling_st']) !!}
                    </td>
                    <td class="px-3 py-2.5 text-center font-medium">
                        {{ $unit['sailing'] ?? '–' }}
                    </td>
                    <td class="px-2 py-2.5 text-center">
                        {!! stBadge($unit['sailing_st']) !!}
                    </td>
                    <td class="px-3 py-2.5 text-center font-medium">
                        {{ $unit['dooring'] ?? '–' }}
                    </td>
                    <td class="px-2 py-2.5 text-center">
                        {!! stBadge($unit['dooring_st']) !!}
                    </td>
                    <td class="px-3 py-2.5 text-center font-semibold {{ ($unit['lt_status'] === 'OK') ? 'text-emerald-600' : (($unit['lt_status'] === 'LATE') ? 'text-red-600' : 'text-gray-400') }}">
                        {{ $unit['lt_total'] ?? '–' }}
                    </td>
                    <td class="px-2 py-2.5 text-center">
                        {!! stBadge($unit['lt_status']) !!}
                    </td>
                    <td class="px-3 py-2.5 text-right">
                        <button wire:click="openUnit({{ $unit['shipment_id'] }})"
                                class="text-xs text-primary-600 hover:text-primary-800 font-medium inline-flex items-center gap-0.5">
                            Detail <x-heroicon-o-chevron-right class="w-3.5 h-3.5" />
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="12" class="px-4 py-10 text-center text-gray-400">
                        <x-heroicon-o-inbox class="w-10 h-10 mx-auto mb-2 text-gray-300" />
                        Tidak ada unit pada voyage ini.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if ($totalPages > 1)
    <div class="mt-3 flex items-center justify-between text-sm text-gray-500">
        <span>Halaman {{ $unitPage }} dari {{ $totalPages }} &bull; {{ count($voyageUnits) }} unit</span>
        <div class="flex gap-2">
            <button wire:click="prevPage" @disabled($unitPage <= 1)
                    class="px-3 py-1.5 rounded-lg border border-gray-300 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 disabled:opacity-40 disabled:cursor-not-allowed transition text-xs">
                ← Prev
            </button>
            <button wire:click="nextPage" @disabled($unitPage >= $totalPages)
                    class="px-3 py-1.5 rounded-lg border border-gray-300 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 disabled:opacity-40 disabled:cursor-not-allowed transition text-xs">
                Next →
            </button>
        </div>
    </div>
    @endif

    @endif {{-- end voyage_detail view --}}


    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    {{-- VIEW: UNIT DETAIL                                                         --}}
    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    @if ($currentView === 'unit_detail')

    {{-- Breadcrumb --}}
    <nav class="mb-4 flex items-center gap-1 text-sm text-gray-500">
        <button wire:click="backToList" class="hover:text-primary-600 transition">Evaluasi Voyage</button>
        <x-heroicon-o-chevron-right class="w-4 h-4 text-gray-300" />
        <button wire:click="backToVoyage" class="hover:text-primary-600 transition">{{ $unitDetail['voyage_label'] ?? '–' }}</button>
        <x-heroicon-o-chevron-right class="w-4 h-4 text-gray-300" />
        <span class="font-semibold text-gray-800 dark:text-white">
            {{ $unitDetail['units'][0]['chassis_no'] ?? ($unitDetail['shipment_code'] ?? 'Unit Detail') }}
        </span>
    </nav>

    @php $d = $unitDetail; @endphp

    @if (empty($d))
        <div class="text-center text-gray-400 py-20">Data tidak ditemukan.</div>
    @else

    {{-- Info card --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-5">

        {{-- Identitas --}}
        <div class="col-span-1 lg:col-span-2 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-4">
            <h3 class="text-xs uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-3 font-semibold">Informasi Unit</h3>
            <dl class="grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
                @foreach ($d['units'] as $u)
                    <div class="col-span-2 border-b border-dashed border-gray-100 dark:border-gray-700 pb-2 mb-1">
                        <div class="flex gap-6">
                            <div>
                                <dt class="text-xs text-gray-400">No Rangka</dt>
                                <dd class="font-mono font-semibold text-gray-900 dark:text-white">{{ $u['chassis_no'] }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-gray-400">No Mesin</dt>
                                <dd class="font-mono text-gray-700 dark:text-gray-300">{{ $u['engine_no'] }}</dd>
                            </div>
                            @if($u['model'] !== '-')
                            <div>
                                <dt class="text-xs text-gray-400">Model</dt>
                                <dd class="text-gray-700 dark:text-gray-300">{{ $u['model'] }}</dd>
                            </div>
                            @endif
                            @if($u['color'] !== '-')
                            <div>
                                <dt class="text-xs text-gray-400">Warna</dt>
                                <dd class="text-gray-700 dark:text-gray-300">{{ $u['color'] }}</dd>
                            </div>
                            @endif
                        </div>
                    </div>
                @endforeach

                <div>
                    <dt class="text-xs text-gray-400">Voyage</dt>
                    <dd class="font-semibold text-gray-800 dark:text-white">{{ $d['voyage_label'] }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-gray-400">Periode</dt>
                    <dd class="text-gray-700 dark:text-gray-300">{{ $d['period'] ?? '–' }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-gray-400">Moda</dt>
                    <dd class="text-gray-700 dark:text-gray-300">{{ $d['mode'] }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-gray-400">Shipment</dt>
                    <dd class="font-mono text-gray-700 dark:text-gray-300 text-xs">{{ $d['shipment_code'] }}</dd>
                </div>
            </dl>
        </div>

        {{-- Overall badge --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-4 flex flex-col justify-center items-center">
            @php
                $badge = $d['badge'] ?? 'Pending';
                $badgeColor = match($badge) {
                    'On Time', 'Tepat Waktu' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
                    'Late', 'Terlambat'      => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                    default                  => 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400',
                };
            @endphp
            <span class="px-5 py-2.5 rounded-xl text-base font-bold {{ $badgeColor }}">
                {{ $badge === 'Late' || $badge === 'Terlambat' ? 'TERLAMBAT (NG)' : ($badge === 'Pending' ? 'PENDING' : 'TEPAT WAKTU (OK)') }}
            </span>
            @if($d['lt_total'] !== null)
            <div class="mt-3 text-center">
                <div class="text-3xl font-black text-gray-900 dark:text-white">{{ $d['lt_total'] }}</div>
                <div class="text-xs text-gray-400">hari lead time total</div>
                @if($d['lt_limit'])
                <div class="text-xs text-gray-400">Target ≤ {{ $d['lt_limit'] }} hari</div>
                @endif
            </div>
            @endif
        </div>

    </div>

    {{-- KPI Evaluation Cards --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-5">
        @foreach ([
            ['label' => 'Dwelling',  'key' => 'dwelling', 'limit_key' => 'dwelling_limit',  'color_ok' => 'emerald', 'color_ng' => 'red'],
            ['label' => 'Sailing',   'key' => 'sailing',  'limit_key' => 'sailing_limit',   'color_ok' => 'emerald', 'color_ng' => 'red'],
            ['label' => 'Dooring',   'key' => 'dooring',  'limit_key' => 'dooring_limit',   'color_ok' => 'emerald', 'color_ng' => 'red'],
            ['label' => 'LT Total',  'key' => 'lt_total', 'limit_key' => 'lt_limit',        'color_ok' => 'emerald', 'color_ng' => 'red'],
        ] as $metric)
        @php
            $val   = $d[$metric['key']] ?? null;
            $limit = $d[$metric['limit_key']] ?? null;
            $stKey = $metric['key'] === 'lt_total' ? 'lt_status' : ($metric['key'] . '_st');
            $st    = $d[$stKey] ?? 'PENDING';
            $isOk  = $st === 'OK';
            $isNg  = $st === 'LATE';
            $ring  = $isOk ? 'ring-1 ring-emerald-200 dark:ring-emerald-800' : ($isNg ? 'ring-1 ring-red-200 dark:ring-red-800' : '');
        @endphp
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-100 dark:border-gray-700 {{ $ring }}">
            <div class="text-xs text-gray-400 uppercase tracking-wider mb-1">{{ $metric['label'] }}</div>
            <div class="text-2xl font-bold {{ $isOk ? 'text-emerald-600 dark:text-emerald-400' : ($isNg ? 'text-red-600 dark:text-red-400' : 'text-gray-400') }}">
                {{ $val ?? '–' }}
                <span class="text-xs font-normal text-gray-400">hari</span>
            </div>
            @if($limit)
            <div class="text-xs text-gray-400 mt-0.5">Target ≤ {{ $limit }} hari</div>
            @endif
            <div class="mt-1">
                @if($isOk)
                    <span class="px-2 py-0.5 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 text-xs font-semibold rounded">OK</span>
                @elseif($isNg)
                    <span class="px-2 py-0.5 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 text-xs font-semibold rounded">NG</span>
                @else
                    <span class="text-gray-400 text-xs">PENDING</span>
                @endif
            </div>
        </div>
        @endforeach
    </div>

    {{-- Timeline --}}
    @if(!empty($d['timeline']))
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-4 mb-5">
        <h3 class="text-xs uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-4 font-semibold">Timeline Pengiriman</h3>
        <div class="flex flex-wrap items-start gap-0">
            @foreach ($d['timeline'] as $i => $step)
            <div class="flex items-start gap-0">
                {{-- Step node --}}
                <div class="flex flex-col items-center">
                    <div class="w-8 h-8 rounded-full bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-400 flex items-center justify-center text-xs font-bold">
                        {{ $i + 1 }}
                    </div>
                    <div class="mt-1 text-center max-w-[100px]">
                        <div class="text-xs font-semibold text-gray-700 dark:text-gray-300 leading-tight">{{ $step['label'] }}</div>
                        <div class="text-[11px] text-gray-400 mt-0.5">{{ $step['at'] }}</div>
                    </div>
                </div>
                {{-- Connector --}}
                @if($i < count($d['timeline']) - 1)
                <div class="flex flex-col items-center mt-3.5 mx-1">
                    <div class="h-0.5 w-12 bg-gray-200 dark:bg-gray-700 relative">
                        @if($d['timeline'][$i + 1]['days'] !== null)
                        <span class="absolute -top-4 left-1/2 -translate-x-1/2 text-[10px] text-gray-400 whitespace-nowrap">
                            {{ $d['timeline'][$i + 1]['days'] }}h
                        </span>
                        @endif
                    </div>
                </div>
                @endif
            </div>
            @endforeach
        </div>
    </div>
    @endif

    @endif {{-- end if $d not empty --}}
    @endif {{-- end unit_detail view --}}

</x-filament-panels::page>
