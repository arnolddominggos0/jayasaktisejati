<x-filament-panels::page>

    {{-- ── Tab Navigation ─────────────────────────────────────────────────────── --}}
    <div class="mt-2">
        <div class="flex flex-wrap gap-1 border-b border-gray-200 dark:border-gray-700">
            @foreach ($this->getTabs() as $tabKey => $tabLabel)
                @php
                    $isActive = $activeTab === $tabKey;
                    $icons = [
                        'overview'            => 'heroicon-m-chart-pie',
                        'mp_readiness'        => 'heroicon-m-users',
                        'container_readiness' => 'heroicon-m-archive-box',
                        'ready_loading'       => 'heroicon-m-check-circle',
                        'waiting_inspection'  => 'heroicon-m-clock',
                        'bermasalah'          => 'heroicon-m-exclamation-triangle',
                        'shipment_readiness'  => 'heroicon-m-chart-bar',
                    ];
                    $activeColors = [
                        'overview'            => 'border-primary-500 text-primary-600 dark:text-primary-400',
                        'mp_readiness'        => 'border-blue-500 text-blue-600 dark:text-blue-400',
                        'container_readiness' => 'border-sky-500 text-sky-600 dark:text-sky-400',
                        'ready_loading'       => 'border-success-500 text-success-600 dark:text-success-400',
                        'waiting_inspection'  => 'border-warning-500 text-warning-600 dark:text-warning-400',
                        'bermasalah'          => 'border-danger-500 text-danger-600 dark:text-danger-400',
                        'shipment_readiness'  => 'border-info-500 text-info-600 dark:text-info-400',
                    ];
                @endphp
                <button
                    wire:click="setTab('{{ $tabKey }}')"
                    type="button"
                    @class([
                        'inline-flex items-center gap-1.5 px-4 py-2.5 text-sm font-medium border-b-2 -mb-px transition-colors duration-150 focus:outline-none',
                        'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-200' => ! $isActive,
                        $activeColors[$tabKey] => $isActive,
                    ])
                >
                    <x-dynamic-component :component="$icons[$tabKey]" class="w-4 h-4" />
                    {{ $tabLabel }}
                </button>
            @endforeach
        </div>
    </div>

    {{-- ── Filter Bar (historical / analytical tabs only) ──────────────────────── --}}
    @if (in_array($activeTab, ['overview', 'mp_readiness', 'container_readiness']))
    <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <div class="flex flex-wrap items-end gap-4">

            {{-- Bulan --}}
            <div class="flex flex-col gap-1">
                <label class="text-xs font-medium text-gray-500 dark:text-gray-400">Bulan</label>
                <select wire:model.live="filterMonth"
                    class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm
                           focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500
                           dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                    @foreach ($month_options as $val => $label)
                        <option value="{{ $val }}" @selected((int) $val === $this->filterMonth)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Tahun --}}
            <div class="flex flex-col gap-1">
                <label class="text-xs font-medium text-gray-500 dark:text-gray-400">Tahun</label>
                <select wire:model.live="filterYear"
                    class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm
                           focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500
                           dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                    @foreach ($year_options as $val => $label)
                        <option value="{{ $val }}" @selected((int) $val === $this->filterYear)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Depot (hanya untuk user multi-depot) --}}
            @if ($has_multi_depot)
                <div class="flex flex-col gap-1">
                    <label class="text-xs font-medium text-gray-500 dark:text-gray-400">Depot</label>
                    <select wire:model.live="filterDepotId"
                        class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm
                               focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500
                               dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                        <option value="">Semua Depot</option>
                        @foreach ($depot_options as $id => $name)
                            <option value="{{ $id }}" @selected((int) $id === $this->filterDepotId)>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div class="ml-auto flex items-end">
                <div class="rounded-lg bg-primary-50 px-4 py-2 dark:bg-primary-900/20">
                    <p class="text-xs font-medium text-primary-500 dark:text-primary-400">Periode</p>
                    <p class="text-sm font-bold text-primary-700 dark:text-primary-300">{{ $month_label }}</p>
                </div>
            </div>

        </div>
    </div>
    @endif

    {{-- ════════════════════════════════════════════════════════════════════════
         TAB: OVERVIEW — KPI summary + Operational Readiness matrix
    ════════════════════════════════════════════════════════════════════════ --}}
    @if ($activeTab === 'overview')

        {{-- KPI Summary Cards --}}
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-5">

            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">Total Briefing</p>
                        <p class="mt-1 text-3xl font-bold text-gray-950 dark:text-white">{{ $total_briefing }}</p>
                    </div>
                    <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg bg-primary-50 dark:bg-primary-900/20">
                        <x-heroicon-o-clipboard-document-check class="h-5 w-5 text-primary-600 dark:text-primary-400" />
                    </div>
                </div>
                <p class="mt-2 text-xs text-gray-400 dark:text-gray-500">Sesi briefing pada periode ini</p>
            </div>

            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">Total Actual Unit Handover</p>
                        <p class="mt-1 text-3xl font-bold text-sky-600 dark:text-sky-400">{{ $total_unit_masuk }}</p>
                    </div>
                    <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg bg-sky-50 dark:bg-sky-900/20">
                        <x-heroicon-o-cube class="h-5 w-5 text-sky-600 dark:text-sky-400" />
                    </div>
                </div>
                <p class="mt-2 text-xs text-gray-400 dark:text-gray-500">Unit masuk Yard/PDC periode ini</p>
            </div>

            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">Kebutuhan Tim SOP</p>
                        <p class="mt-1 text-3xl font-bold text-gray-950 dark:text-white">{{ $total_need }}</p>
                    </div>
                    <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg bg-amber-50 dark:bg-amber-900/20">
                        <x-heroicon-o-users class="h-5 w-5 text-amber-600 dark:text-amber-400" />
                    </div>
                </div>
                <p class="mt-2 text-xs text-gray-400 dark:text-gray-500">Kumulatif kebutuhan minimum operasional</p>
            </div>

            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">Total MP Attend</p>
                        <p class="mt-1 text-3xl font-bold text-gray-950 dark:text-white">{{ $total_attend }}</p>
                    </div>
                    <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg bg-emerald-50 dark:bg-emerald-900/20">
                        <x-heroicon-o-user-group class="h-5 w-5 text-emerald-600 dark:text-emerald-400" />
                    </div>
                </div>
                <p class="mt-2 text-xs text-gray-400 dark:text-gray-500">Kumulatif kehadiran MP (present)</p>
            </div>

            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">Readiness OK</p>
                        <p class="mt-1 text-3xl font-bold
                            {{ $readiness_ok >= 80 ? 'text-emerald-600' : ($readiness_ok >= 50 ? 'text-amber-600' : 'text-rose-600') }}">
                            {{ $readiness_ok }}%
                        </p>
                    </div>
                    <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg
                        {{ $readiness_ok >= 80 ? 'bg-emerald-50' : ($readiness_ok >= 50 ? 'bg-amber-50' : 'bg-rose-50') }}">
                        <x-heroicon-o-check-badge class="h-5 w-5
                            {{ $readiness_ok >= 80 ? 'text-emerald-600' : ($readiness_ok >= 50 ? 'text-amber-600' : 'text-rose-600') }}" />
                    </div>
                </div>
                <div class="mt-2 flex items-center gap-2">
                    <span class="text-xs font-semibold text-emerald-600">OK: {{ $ok_count }} hari</span>
                    <span class="text-xs text-gray-300">·</span>
                    <span class="text-xs font-semibold text-rose-600">NG: {{ $ng_count }} hari</span>
                </div>
            </div>

        </div>

        {{-- Operational Readiness (Gabungan MP + Container) --}}
        @if (count($operational_readiness) > 0)
        <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">

            <div class="flex items-center gap-2 border-b border-gray-200 px-5 py-4 dark:border-gray-700">
                <x-heroicon-o-check-badge class="h-4 w-4 text-primary-500 dark:text-primary-400" />
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200">
                    Operational Readiness — {{ $month_label }}
                </h3>
                <span class="ml-2 rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-500 dark:bg-gray-800 dark:text-gray-400">
                    MP Readiness + Container Readiness
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800/60">
                            <th class="px-4 py-3 text-left   text-xs font-semibold uppercase tracking-wider text-gray-500">Tanggal</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-blue-500">MP Readiness</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-sky-500">Container Readiness</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-700 dark:text-gray-300">Overall</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach ($operational_readiness as $or)
                            <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-800/40">

                                <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                                    {{ $or['date_label'] }}
                                </td>

                                <td class="px-4 py-3 text-center">
                                    @if (! $or['has_mp'])
                                        <span class="text-xs text-gray-300 dark:text-gray-600">Tidak ada data</span>
                                    @elseif ($or['mp_ok'])
                                        <div class="flex flex-col items-center gap-0.5">
                                            <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-bold text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300">OK</span>
                                            <span class="text-xs text-gray-400">{{ $or['mp_attend'] }}/{{ $or['mp_need'] }} hadir</span>
                                        </div>
                                    @else
                                        <div class="flex flex-col items-center gap-0.5">
                                            <span class="inline-flex items-center rounded-full bg-rose-100 px-2.5 py-0.5 text-xs font-bold text-rose-800 dark:bg-rose-900/30 dark:text-rose-300">NG</span>
                                            <span class="text-xs text-gray-400">{{ $or['mp_attend'] }}/{{ $or['mp_need'] }} hadir</span>
                                        </div>
                                    @endif
                                </td>

                                <td class="px-4 py-3 text-center">
                                    @if (! $or['has_container'])
                                        <span class="text-xs text-gray-300 dark:text-gray-600">Tidak ada data</span>
                                    @elseif ($or['container_ok'])
                                        <div class="flex flex-col items-center gap-0.5">
                                            <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-bold text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300">OK</span>
                                            <span class="text-xs text-gray-400">{{ $or['container_avail'] }}/{{ $or['container_need'] }} avail</span>
                                        </div>
                                    @else
                                        <div class="flex flex-col items-center gap-0.5">
                                            <span class="inline-flex items-center rounded-full bg-rose-100 px-2.5 py-0.5 text-xs font-bold text-rose-800 dark:bg-rose-900/30 dark:text-rose-300">NG</span>
                                            <span class="text-xs text-gray-400">{{ $or['container_avail'] }}/{{ $or['container_need'] }} avail</span>
                                        </div>
                                    @endif
                                </td>

                                <td class="px-4 py-3 text-center">
                                    @if ($or['overall_ok'] === null)
                                        <span class="text-xs text-gray-300 dark:text-gray-600">—</span>
                                    @elseif ($or['overall_ok'])
                                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300">
                                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                            READY
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 rounded-full bg-rose-100 px-3 py-1 text-xs font-bold text-rose-800 dark:bg-rose-900/30 dark:text-rose-300">
                                            <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-rose-500"></span>
                                            NOT READY
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>

                    @php
                        $overallReady    = collect($operational_readiness)->where('overall_ok', true)->count();
                        $overallNotReady = collect($operational_readiness)->where('overall_ok', false)->count();
                        $overallNoData   = collect($operational_readiness)->where('overall_ok', null)->count();
                    @endphp
                    @if ($overallReady + $overallNotReady > 0)
                        <tfoot>
                            <tr class="border-t-2 border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800/60">
                                <td class="px-4 py-3 text-xs font-bold uppercase tracking-wider text-gray-500" colspan="3">Ringkasan</td>
                                <td class="px-4 py-3 text-center">
                                    <div class="flex justify-center gap-1.5">
                                        @if ($overallReady > 0)
                                            <span class="inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-bold text-emerald-800">{{ $overallReady }} READY</span>
                                        @endif
                                        @if ($overallNotReady > 0)
                                            <span class="inline-flex items-center rounded-full bg-rose-100 px-2 py-0.5 text-xs font-bold text-rose-800">{{ $overallNotReady }} NG</span>
                                        @endif
                                        @if ($overallNoData > 0)
                                            <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-500">{{ $overallNoData }} —</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
        @else
            <div class="rounded-xl bg-white p-10 text-center shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <x-heroicon-o-check-badge class="mx-auto h-12 w-12 text-gray-200 dark:text-gray-700" />
                <p class="mt-3 text-sm font-medium text-gray-400 dark:text-gray-500">Belum ada data readiness untuk periode ini</p>
            </div>
        @endif

    {{-- ════════════════════════════════════════════════════════════════════════
         TAB: MP READINESS — Monitoring harian briefing + drill-down + matrix
    ════════════════════════════════════════════════════════════════════════ --}}
    @elseif ($activeTab === 'mp_readiness')

        @if ($total_briefing === 0)
            <div class="rounded-xl bg-white p-16 text-center shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <x-heroicon-o-clipboard-document-check class="mx-auto h-14 w-14 text-gray-200 dark:text-gray-700" />
                <p class="mt-4 text-base font-semibold text-gray-500 dark:text-gray-400">
                    Tidak ada data briefing pada periode ini
                </p>
                <p class="mt-1 text-sm text-gray-400 dark:text-gray-500">
                    Pilih bulan dan tahun yang memiliki data briefing harian.
                </p>
            </div>
        @else

            {{-- Tabel Monitoring Harian --}}
            <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10"
                 id="daily-table">

                <div class="flex items-center gap-2 border-b border-gray-200 px-5 py-4 dark:border-gray-700">
                    <x-heroicon-o-table-cells class="h-4 w-4 text-gray-400" />
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200">
                        Monitoring Harian — {{ $month_label }}
                    </h3>
                    <span class="ml-1 rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-500 dark:bg-gray-800 dark:text-gray-400">
                        {{ $total_briefing }} sesi
                    </span>
                    @if ($selected_session_id)
                        <span class="ml-auto rounded-full bg-primary-50 px-2.5 py-0.5 text-xs font-semibold text-primary-700 dark:bg-primary-900/30 dark:text-primary-300">
                            Detail aktif — klik baris lagi untuk tutup
                        </span>
                    @endif
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800/60">
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Tanggal</th>
                                @if ($has_multi_depot)
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Depot</th>
                                @endif
                                <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-sky-500 dark:text-sky-400">Actual Unit Handover</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Kebutuhan Tim</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">MP Attend</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Gap</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Status</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Aksi</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach ($rows as $row)
                                @php $isSelected = $selected_session_id === $row['session_id']; @endphp
                                <tr class="transition-colors
                                    {{ $isSelected
                                        ? 'bg-primary-50 dark:bg-primary-900/10'
                                        : 'hover:bg-gray-50 dark:hover:bg-gray-800/40' }}">

                                    <td class="px-4 py-3 font-medium {{ $isSelected ? 'text-primary-700 dark:text-primary-300' : 'text-gray-900 dark:text-white' }}">
                                        {{ $row['date_label'] }}
                                    </td>
                                    @if ($has_multi_depot)
                                        <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ $row['depot'] }}</td>
                                    @endif
                                    <td class="px-4 py-3 text-center">
                                        @if ($row['unit_masuk'] !== null)
                                            <span class="font-semibold text-sky-700 dark:text-sky-400">{{ $row['unit_masuk'] }}</span>
                                        @else
                                            <span class="text-gray-300 dark:text-gray-600">—</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-center text-gray-700 dark:text-gray-300">
                                        {{ $row['mp_need'] > 0 ? $row['mp_need'] : '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-center font-semibold text-gray-900 dark:text-white">
                                        {{ $row['mp_attend'] }}
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        @if ($row['ok'] === true)
                                            <span class="font-semibold text-emerald-600 dark:text-emerald-400">{{ $row['gap_label'] }}</span>
                                        @elseif ($row['ok'] === false)
                                            <span class="font-semibold text-rose-600 dark:text-rose-400">{{ $row['gap_label'] }}</span>
                                        @else
                                            <span class="text-gray-400">{{ $row['gap_label'] }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        @if ($row['status'] === 'OK')
                                            <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-bold text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300">OK</span>
                                        @elseif ($row['status'] === 'NG')
                                            <span class="inline-flex items-center rounded-full bg-rose-100 px-2.5 py-0.5 text-xs font-bold text-rose-800 dark:bg-rose-900/30 dark:text-rose-300">NG</span>
                                        @else
                                            <span class="text-gray-300 dark:text-gray-600">—</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <button wire:click="selectSession({{ $row['session_id'] }})"
                                            class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold
                                                   transition-colors focus:outline-none
                                                   {{ $isSelected
                                                       ? 'bg-primary-600 text-white shadow-sm'
                                                       : 'bg-primary-50 text-primary-700 hover:bg-primary-100 dark:bg-primary-900/20 dark:text-primary-300 dark:hover:bg-primary-900/40' }}">
                                            @if ($isSelected)
                                                <x-heroicon-o-chevron-up class="h-3.5 w-3.5" />
                                                Tutup
                                            @else
                                                <x-heroicon-o-eye class="h-3.5 w-3.5" />
                                                Detail
                                            @endif
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>

                        <tfoot>
                            <tr class="border-t-2 border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800/60">
                                <td class="px-4 py-3 text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    Total ({{ $total_briefing }} hari)
                                </td>
                                @if ($has_multi_depot)
                                    <td></td>
                                @endif
                                <td class="px-4 py-3 text-center font-bold text-sky-700 dark:text-sky-400">{{ $total_unit_masuk }}</td>
                                <td class="px-4 py-3 text-center font-semibold text-gray-700 dark:text-gray-300">{{ $total_need }}</td>
                                <td class="px-4 py-3 text-center font-bold text-gray-900 dark:text-white">{{ $total_attend }}</td>
                                <td class="px-4 py-3 text-center">
                                    <span class="font-bold {{ $total_gap >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                                        {{ $total_gap >= 0 ? "+{$total_gap}" : $total_gap }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <div class="flex justify-center gap-1.5">
                                        <span class="inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-bold text-emerald-800">{{ $ok_count }} OK</span>
                                        <span class="inline-flex items-center rounded-full bg-rose-100 px-2 py-0.5 text-xs font-bold text-rose-800">{{ $ng_count }} NG</span>
                                    </div>
                                </td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            {{-- Detail Drill-down --}}
            @if ($detail)
                <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-2 ring-primary-200 dark:bg-gray-900 dark:ring-primary-800"
                     x-data
                     x-init="$el.scrollIntoView({ behavior: 'smooth', block: 'start' })"
                     id="detail-panel">

                    <div class="flex flex-wrap items-center gap-3 border-b border-primary-100 bg-primary-50 px-5 py-4
                                dark:border-primary-900/30 dark:bg-primary-900/10">

                        <div class="flex items-center gap-2">
                            <x-heroicon-o-user-circle class="h-5 w-5 text-primary-600 dark:text-primary-400" />
                            <h3 class="text-sm font-bold text-primary-800 dark:text-primary-200">
                                Detail Kehadiran — {{ $detail['date_label'] }}
                            </h3>
                            @if ($has_multi_depot)
                                <span class="text-sm text-primary-600 dark:text-primary-400">· {{ $detail['depot'] }}</span>
                            @endif
                        </div>

                        <div class="flex flex-wrap items-center gap-3 text-sm">
                            @if ($detail['unit_masuk'] !== null)
                                <span class="rounded-lg bg-sky-50 px-3 py-1 text-xs font-medium text-sky-700 shadow-sm ring-1 ring-sky-200 dark:bg-sky-900/20 dark:ring-sky-800 dark:text-sky-300">
                                    Actual Unit Handover: <strong>{{ $detail['unit_masuk'] }}</strong>
                                </span>
                            @endif
                            <span class="rounded-lg bg-white px-3 py-1 text-xs font-medium text-gray-600 shadow-sm ring-1 ring-gray-200 dark:bg-gray-800 dark:ring-gray-700 dark:text-gray-300">
                                Need: <strong>{{ $detail['mp_need'] > 0 ? $detail['mp_need'] : '—' }}</strong>
                            </span>
                            <span class="rounded-lg bg-white px-3 py-1 text-xs font-medium text-gray-600 shadow-sm ring-1 ring-gray-200 dark:bg-gray-800 dark:ring-gray-700 dark:text-gray-300">
                                Attend: <strong>{{ $detail['mp_attend'] }}</strong>
                            </span>
                            <span class="rounded-lg bg-white px-3 py-1 text-xs font-semibold shadow-sm ring-1 ring-gray-200 dark:bg-gray-800 dark:ring-gray-700
                                {{ $detail['ok'] === true ? 'text-emerald-700' : ($detail['ok'] === false ? 'text-rose-700' : 'text-gray-500') }}">
                                Gap: {{ $detail['gap_label'] }}
                            </span>
                            @if ($detail['status'] === 'OK')
                                <span class="inline-flex items-center rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-800">
                                    ✓ Readiness OK
                                </span>
                            @elseif ($detail['status'] === 'NG')
                                <span class="inline-flex items-center rounded-full bg-rose-100 px-3 py-1 text-xs font-bold text-rose-800">
                                    ✗ Readiness NG
                                </span>
                            @endif
                        </div>

                        <button wire:click="closeDetail"
                            class="ml-auto flex items-center gap-1.5 rounded-lg bg-white px-3 py-1.5 text-xs font-semibold
                                   text-gray-600 shadow-sm ring-1 ring-gray-200 transition-colors hover:bg-gray-50
                                   dark:bg-gray-800 dark:text-gray-300 dark:ring-gray-700 dark:hover:bg-gray-700">
                            <x-heroicon-o-x-mark class="h-3.5 w-3.5" />
                            Tutup
                        </button>
                    </div>

                    @if ($detail['evidence_url'])
                        <div class="border-b border-gray-100 px-5 py-4 dark:border-gray-800">
                            <p class="mb-3 flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">
                                <x-heroicon-o-photo class="h-3.5 w-3.5" />
                                Foto Briefing
                            </p>
                            <a href="{{ $detail['evidence_url'] }}" target="_blank" rel="noopener noreferrer"
                               class="inline-block">
                                <img src="{{ $detail['evidence_url'] }}"
                                     alt="Bukti Briefing {{ $detail['date_label'] }}"
                                     class="max-h-72 max-w-full rounded-xl object-contain ring-1 ring-gray-200
                                            transition-opacity hover:opacity-80 dark:ring-gray-700">
                            </a>
                            <p class="mt-2 text-xs text-gray-400 dark:text-gray-500">
                                Klik gambar untuk membuka ukuran penuh.
                            </p>
                        </div>
                    @endif

                    @if ($detail['attendances']->isEmpty())
                        <div class="p-10 text-center">
                            <x-heroicon-o-users class="mx-auto h-10 w-10 text-gray-200" />
                            <p class="mt-3 text-sm text-gray-400">Belum ada data kehadiran untuk sesi ini.</p>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800/60">
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">#</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Nama MP</th>
                                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">Status Kehadiran</th>
                                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">Suhu</th>
                                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">Tensi</th>
                                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">Fit</th>
                                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">Recheck</th>
                                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">APD</th>
                                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">Status Akhir</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                    @foreach ($detail['attendances'] as $i => $att)
                                        <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-800/40
                                            {{ $att['status_value'] !== 'present' ? 'opacity-60' : '' }}">

                                            <td class="px-4 py-3 text-xs text-gray-400">{{ $i + 1 }}</td>

                                            <td class="px-4 py-3">
                                                <div class="flex items-center gap-2">
                                                    <span class="font-semibold text-gray-900 dark:text-white">{{ $att['name'] }}</span>
                                                    @if ($att['is_backup'])
                                                        <span class="rounded bg-amber-100 px-1.5 py-0.5 text-xs font-medium text-amber-700">Backup</span>
                                                    @endif
                                                </div>
                                            </td>

                                            <td class="px-4 py-3 text-center">
                                                @php
                                                    $statusCls = match ($att['status_value']) {
                                                        'present' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300',
                                                        'sick'    => 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300',
                                                        'leave'   => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
                                                        default   => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
                                                    };
                                                @endphp
                                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $statusCls }}">
                                                    {{ $att['status_label'] }}
                                                </span>
                                            </td>

                                            <td class="px-4 py-3 text-center text-xs">
                                                @if ($att['temperature'])
                                                    @php
                                                        $tempVal = (float) str_replace('°C', '', $att['temperature']);
                                                        $tempOk  = $tempVal >= 36.5 && $tempVal <= 37.5;
                                                    @endphp
                                                    <span class="font-medium {{ $tempOk ? 'text-gray-700' : 'text-rose-600' }}">
                                                        {{ $att['temperature'] }}
                                                    </span>
                                                @else
                                                    <span class="text-gray-300">—</span>
                                                @endif
                                            </td>

                                            <td class="px-4 py-3 text-center text-xs text-gray-600 dark:text-gray-400">
                                                {{ $att['bp'] ?? '—' }}
                                            </td>

                                            <td class="px-4 py-3 text-center">
                                                @if ($att['fit_status'])
                                                    @php $fitOk = strtoupper((string) $att['fit_status']) === 'FIT'; @endphp
                                                    <span class="inline-block rounded px-1.5 py-0.5 text-xs font-bold
                                                        {{ $fitOk ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' }}">
                                                        {{ strtoupper((string) $att['fit_status']) }}
                                                    </span>
                                                @else
                                                    <span class="text-gray-300 text-xs">—</span>
                                                @endif
                                            </td>

                                            <td class="px-4 py-3 text-center">
                                                @if ($att['recheck_result'])
                                                    @php $recheckOk = strtoupper((string) $att['recheck_result']) === 'FIT'; @endphp
                                                    <span class="inline-block rounded px-1.5 py-0.5 text-xs font-bold
                                                        {{ $recheckOk ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' }}">
                                                        {{ strtoupper((string) $att['recheck_result']) }}
                                                    </span>
                                                @else
                                                    <span class="text-gray-300 text-xs">—</span>
                                                @endif
                                            </td>

                                            <td class="px-4 py-3 text-center">
                                                @if ($att['status_value'] === 'present')
                                                    @if ($att['has_ppe'])
                                                        <x-heroicon-o-check-circle class="mx-auto h-4 w-4 text-emerald-500" />
                                                    @else
                                                        <x-heroicon-o-x-circle class="mx-auto h-4 w-4 text-rose-500" />
                                                    @endif
                                                @else
                                                    <span class="text-gray-300 text-xs">—</span>
                                                @endif
                                            </td>

                                            <td class="px-4 py-3 text-center">
                                                @php
                                                    $finalCls = match ($att['final_color']) {
                                                        'emerald' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300',
                                                        'rose'    => 'bg-rose-100 text-rose-800 dark:bg-rose-900/30 dark:text-rose-300',
                                                        'amber'   => 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300',
                                                        default   => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
                                                    };
                                                @endphp
                                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $finalCls }}">
                                                    {{ $att['final_status'] }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        @php
                            $siapKerja   = $detail['attendances']->where('final_status', 'Siap Kerja')->count();
                            $tidakHadir  = $detail['attendances']->where('status_value', '!=', 'present')->count();
                            $masalah     = $detail['attendances']->filter(
                                fn ($a) => in_array($a['final_status'], [
                                    'Tidak Fit', 'APD Tidak Lengkap',
                                    'Istirahat 30 Menit', 'Perlu Pemeriksaan Ulang',
                                ])
                            )->count();
                            $totalMp     = $detail['attendances']->count();
                        @endphp
                        <div class="flex flex-wrap gap-3 border-t border-gray-100 bg-gray-50 px-5 py-3 dark:border-gray-800 dark:bg-gray-800/40">
                            <span class="text-xs font-medium text-gray-500">Ringkasan:</span>
                            <span class="text-xs font-semibold text-emerald-700">
                                <span class="font-bold">{{ $siapKerja }}</span> Siap Kerja
                            </span>
                            @if ($masalah > 0)
                                <span class="text-xs font-semibold text-amber-700">
                                    <span class="font-bold">{{ $masalah }}</span> Butuh Perhatian
                                </span>
                            @endif
                            @if ($tidakHadir > 0)
                                <span class="text-xs font-semibold text-gray-500">
                                    <span class="font-bold">{{ $tidakHadir }}</span> Tidak Hadir
                                </span>
                            @endif
                            <span class="text-xs text-gray-400">dari {{ $totalMp }} MP terdaftar</span>
                        </div>
                    @endif
                </div>
            @endif

            {{-- Matrix Bulanan --}}
            <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">

                <div class="flex flex-wrap items-center gap-2 border-b border-gray-200 px-5 py-4 dark:border-gray-700">
                    <x-heroicon-o-calendar-days class="h-4 w-4 text-gray-400" />
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200">
                        Matrix Bulanan — {{ $month_label }}
                    </h3>
                    <span class="text-xs text-gray-400 dark:text-gray-500">
                        · scroll horizontal untuk melihat semua tanggal
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="border-separate border-spacing-0 text-xs">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-800/60">
                                <th class="sticky left-0 z-10 min-w-[80px] whitespace-nowrap border-b border-r border-gray-200
                                           bg-gray-50 px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider
                                           text-gray-500 dark:border-gray-700 dark:bg-gray-800/60 dark:text-gray-400">
                                    Item
                                </th>
                                @for ($d = 1; $d <= $days_in_month; $d++)
                                    @php
                                        $mDay   = $matrix[$d] ?? null;
                                        $isWknd = $mDay
                                            ? in_array($mDay['date']->dayOfWeek, [0, 6])
                                            : false;
                                    @endphp
                                    <th class="min-w-[38px] border-b border-gray-200 px-1.5 py-3 text-center font-semibold
                                               dark:border-gray-700
                                               {{ $isWknd ? 'text-amber-500' : 'text-gray-500 dark:text-gray-400' }}">
                                        {{ $d }}
                                    </th>
                                @endfor
                            </tr>
                        </thead>

                        <tbody>
                            <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-800/30">
                                <td class="sticky left-0 z-10 whitespace-nowrap border-b border-r border-gray-100
                                           bg-white px-4 py-2.5 font-semibold text-sky-700
                                           dark:border-gray-800 dark:bg-gray-900 dark:text-sky-400">
                                    Unit
                                </td>
                                @for ($d = 1; $d <= $days_in_month; $d++)
                                    @php $mRow = $matrix[$d] ?? null; @endphp
                                    <td class="border-b border-gray-100 px-1.5 py-2.5 text-center font-semibold
                                               text-sky-600 dark:border-gray-800 dark:text-sky-400">
                                        {{ $mRow ? ($mRow['unit_masuk'] > 0 ? $mRow['unit_masuk'] : '—') : '' }}
                                    </td>
                                @endfor
                            </tr>

                            <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-800/30">
                                <td class="sticky left-0 z-10 whitespace-nowrap border-b border-r border-gray-100
                                           bg-white px-4 py-2.5 font-semibold text-gray-700
                                           dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
                                    Need
                                </td>
                                @for ($d = 1; $d <= $days_in_month; $d++)
                                    @php $mRow = $matrix[$d] ?? null; @endphp
                                    <td class="border-b border-gray-100 px-1.5 py-2.5 text-center text-gray-600
                                               dark:border-gray-800 dark:text-gray-400">
                                        {{ $mRow ? ($mRow['mp_need'] > 0 ? $mRow['mp_need'] : '—') : '' }}
                                    </td>
                                @endfor
                            </tr>

                            <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-800/30">
                                <td class="sticky left-0 z-10 whitespace-nowrap border-b border-r border-gray-100
                                           bg-white px-4 py-2.5 font-semibold text-gray-700
                                           dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
                                    Attend
                                </td>
                                @for ($d = 1; $d <= $days_in_month; $d++)
                                    @php $mRow = $matrix[$d] ?? null; @endphp
                                    <td class="border-b border-gray-100 px-1.5 py-2.5 text-center font-semibold
                                               text-gray-900 dark:border-gray-800 dark:text-white">
                                        {{ $mRow !== null ? $mRow['mp_attend'] : '' }}
                                    </td>
                                @endfor
                            </tr>

                            <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-800/30">
                                <td class="sticky left-0 z-10 whitespace-nowrap border-b border-r border-gray-100
                                           bg-white px-4 py-2.5 font-semibold text-gray-700
                                           dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
                                    Gap
                                </td>
                                @for ($d = 1; $d <= $days_in_month; $d++)
                                    @php $mRow = $matrix[$d] ?? null; @endphp
                                    <td class="border-b border-gray-100 px-1.5 py-2.5 text-center
                                               dark:border-gray-800">
                                        @if ($mRow !== null)
                                            @if ($mRow['ok'] === true)
                                                <span class="font-semibold text-emerald-600 dark:text-emerald-400">{{ $mRow['gap_label'] }}</span>
                                            @elseif ($mRow['ok'] === false)
                                                <span class="font-semibold text-rose-600 dark:text-rose-400">{{ $mRow['gap_label'] }}</span>
                                            @else
                                                <span class="text-gray-400">—</span>
                                            @endif
                                        @endif
                                    </td>
                                @endfor
                            </tr>

                            <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-800/30">
                                <td class="sticky left-0 z-10 whitespace-nowrap border-r border-gray-100
                                           bg-white px-4 py-2.5 font-semibold text-gray-700
                                           dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
                                    Status
                                </td>
                                @for ($d = 1; $d <= $days_in_month; $d++)
                                    @php $mRow = $matrix[$d] ?? null; @endphp
                                    <td class="px-1 py-2.5 text-center">
                                        @if ($mRow !== null)
                                            @if ($mRow['status'] === 'OK')
                                                <span class="inline-block rounded bg-emerald-100 px-1.5 py-0.5 text-xs
                                                             font-bold text-emerald-800
                                                             dark:bg-emerald-900/30 dark:text-emerald-300">OK</span>
                                            @elseif ($mRow['status'] === 'NG')
                                                <span class="inline-block rounded bg-rose-100 px-1.5 py-0.5 text-xs
                                                             font-bold text-rose-800
                                                             dark:bg-rose-900/30 dark:text-rose-300">NG</span>
                                            @else
                                                <span class="text-gray-300 dark:text-gray-600">—</span>
                                            @endif
                                        @endif
                                    </td>
                                @endfor
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        @endif {{-- end $total_briefing > 0 --}}

    {{-- ════════════════════════════════════════════════════════════════════════
         TAB: CONTAINER READINESS
    ════════════════════════════════════════════════════════════════════════ --}}
    @elseif ($activeTab === 'container_readiness')

        <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">

            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 px-5 py-4 dark:border-gray-700">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-archive-box class="h-4 w-4 text-gray-400" />
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200">
                        Container Readiness — {{ $month_label }}
                    </h3>
                    @if (count($container_rows) > 0)
                        <span class="ml-1 rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-500 dark:bg-gray-800 dark:text-gray-400">
                            {{ count($container_rows) }} hari
                        </span>
                    @endif
                </div>
                <a href="{{ $container_resource_url }}"
                   class="inline-flex items-center gap-1.5 rounded-lg bg-sky-50 px-3 py-1.5 text-xs font-semibold
                          text-sky-700 transition-colors hover:bg-sky-100
                          dark:bg-sky-900/20 dark:text-sky-300 dark:hover:bg-sky-900/40">
                    <x-heroicon-m-plus class="h-3.5 w-3.5" />
                    Input Container
                </a>
            </div>

            @if (count($container_rows) === 0)
                <div class="p-10 text-center">
                    <x-heroicon-o-archive-box class="mx-auto h-10 w-10 text-gray-200 dark:text-gray-700" />
                    <p class="mt-3 text-sm font-medium text-gray-400 dark:text-gray-500">
                        Belum ada data container readiness untuk periode ini
                    </p>
                    <p class="mt-1 text-xs text-gray-400 dark:text-gray-600">
                        Klik "+ Input Container" untuk menambah data.
                    </p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800/60">
                                <th class="px-4 py-3 text-left   text-xs font-semibold uppercase tracking-wider text-gray-500">Tanggal</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-400">Unit</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-sky-500">Need</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-emerald-500">Available</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">Gap</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">Status</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-500">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach ($container_rows as $cr)
                                <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-800/40">
                                    <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ $cr['date_label'] }}</td>
                                    <td class="px-4 py-3 text-center text-gray-500 dark:text-gray-400">{{ $cr['unit_count'] }}</td>
                                    <td class="px-4 py-3 text-center font-semibold text-sky-700 dark:text-sky-400">{{ $cr['container_need'] }}</td>
                                    <td class="px-4 py-3 text-center font-semibold text-emerald-700 dark:text-emerald-400">{{ $cr['container_available'] }}</td>
                                    <td class="px-4 py-3 text-center font-semibold
                                               {{ $cr['is_ready'] ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                                        {{ $cr['gap_label'] }}
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        @if ($cr['is_ready'])
                                            <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-bold text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300">READY</span>
                                        @else
                                            <span class="inline-flex items-center rounded-full bg-rose-100 px-2.5 py-0.5 text-xs font-bold text-rose-800 dark:bg-rose-900/30 dark:text-rose-300">NOT READY</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <a href="{{ $cr['edit_url'] }}"
                                           class="inline-flex items-center gap-1 rounded-lg bg-gray-50 px-3 py-1.5 text-xs font-semibold
                                                  text-gray-600 ring-1 ring-gray-200 transition-colors hover:bg-gray-100
                                                  dark:bg-gray-800 dark:text-gray-300 dark:ring-gray-700 dark:hover:bg-gray-700">
                                            <x-heroicon-m-pencil-square class="h-3.5 w-3.5" />
                                            Edit
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>

                        @php
                            $crCollection  = collect($container_rows);
                            $totalUnitC    = $crCollection->sum('unit_count');
                            $totalNeedC    = $crCollection->sum('container_need');
                            $totalAvailC   = $crCollection->sum('container_available');
                            $totalGapC     = $totalAvailC - $totalNeedC;
                            $readyCount    = $crCollection->where('is_ready', true)->count();
                            $notReadyCount = count($container_rows) - $readyCount;
                        @endphp
                        <tfoot>
                            <tr class="border-t-2 border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800/60">
                                <td class="px-4 py-3 text-xs font-bold uppercase tracking-wider text-gray-500">Total</td>
                                <td class="px-4 py-3 text-center font-bold text-gray-500 dark:text-gray-400">{{ $totalUnitC }}</td>
                                <td class="px-4 py-3 text-center font-bold text-sky-700 dark:text-sky-400">{{ $totalNeedC }}</td>
                                <td class="px-4 py-3 text-center font-bold text-emerald-700 dark:text-emerald-400">{{ $totalAvailC }}</td>
                                <td class="px-4 py-3 text-center font-bold {{ $totalGapC >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                                    {{ $totalGapC >= 0 ? "+{$totalGapC}" : $totalGapC }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <div class="flex justify-center gap-1.5">
                                        @if ($readyCount > 0)
                                            <span class="inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-bold text-emerald-800">{{ $readyCount }} OK</span>
                                        @endif
                                        @if ($notReadyCount > 0)
                                            <span class="inline-flex items-center rounded-full bg-rose-100 px-2 py-0.5 text-xs font-bold text-rose-800">{{ $notReadyCount }} NG</span>
                                        @endif
                                    </div>
                                </td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endif
        </div>

    {{-- ════════════════════════════════════════════════════════════════════════
         OPERATIONAL TABS — Filament table (live data, no date filter)
    ════════════════════════════════════════════════════════════════════════ --}}
    @else
        <div class="mt-3">
            {{ $this->table }}
        </div>
    @endif

</x-filament-panels::page>
