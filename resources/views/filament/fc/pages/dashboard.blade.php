<x-filament-panels::page>

    {{-- ══════════════════════════════════════════════════════════════════════
         Hitung status briefing + kesiapan di awal untuk dipakai badge & section.
    ══════════════════════════════════════════════════════════════════════ --}}
    @php
        $bs        = $this->getTodayBriefingStatus();
        $kesiapan  = $this->getKesiapanOperasional();
        $perhatian = $this->getPerluPerhatian();

        // Chip status briefing — awareness saja, action ada di Tugas Operasional.
        // Flat (tanpa ring) agar sebobot chip lingkup di sebelahnya.
        if (! $bs['has_briefing']) {
            $briefingBadgeCls = 'bg-gray-100 text-gray-600 dark:bg-white/5 dark:text-gray-300';
            $briefingBadgeIco = 'heroicon-m-clock';
            $briefingBadgeTxt = 'Belum Briefing';
        } elseif ($bs['is_ready']) {
            $briefingBadgeCls = 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-400';
            $briefingBadgeIco = 'heroicon-m-check-badge';
            $briefingBadgeTxt = "Briefing Selesai · MP {$bs['fit_count']}/{$bs['need_mp']}";
        } else {
            $briefingBadgeCls = 'bg-amber-50 text-amber-700 dark:bg-amber-900/20 dark:text-amber-400';
            $briefingBadgeIco = 'heroicon-m-exclamation-triangle';
            $briefingBadgeTxt = "Briefing · MP {$bs['fit_count']}/{$bs['need_mp']} Belum Siap";
        }
    @endphp

    {{-- ══════════════════════════════════════════════════════════════════════
         SECTION 1 — LINGKUP OPERASIONAL
         Konteks lokasi: Branch → Depot, dengan badge ringkas status briefing.
         Awareness saja — action Briefing/Container ada di Tugas Operasional.
    ══════════════════════════════════════════════════════════════════════ --}}
    {{-- Context bar — lapisan ketiga header (judul → subtitle → konteks).
         Kedua chip berbagi tinggi, radius, padding, dan ukuran teks yang sama
         supaya terbaca sebagai satu baris konteks, bukan pill yang mengambang. --}}
    <div class="-mt-2 mb-2 flex flex-wrap items-center gap-2">
        {{-- Lingkup operasional: Branch → Depot --}}
        <span class="inline-flex h-7 max-w-full items-center gap-1.5 rounded-lg bg-gray-100 px-2.5 text-[11px] font-medium text-gray-600 dark:bg-white/5 dark:text-gray-300">
            <x-heroicon-m-building-office-2 class="h-3.5 w-3.5 shrink-0 text-gray-400 dark:text-gray-500" />
            <span class="truncate">{{ $this->getBranchName() }}</span>
            @if ($this->hasDepotContext())
                <span class="text-gray-300 dark:text-gray-600">·</span>
                <span class="truncate">{{ $this->getDepotName() }}</span>
            @endif
        </span>

        {{-- Status briefing hari ini --}}
        <span class="inline-flex h-7 max-w-full items-center gap-1.5 rounded-lg px-2.5 text-[11px] font-medium {{ $briefingBadgeCls }}">
            <x-dynamic-component :component="$briefingBadgeIco" class="h-3.5 w-3.5 shrink-0" />
            <span class="truncate">{{ $briefingBadgeTxt }}</span>
        </span>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════════
         SECTION 2 — KESIAPAN OPERASIONAL HARI INI
         2 card: MP Readiness · Container Readiness.
    ══════════════════════════════════════════════════════════════════════ --}}
    <div class="mb-8">
        <p class="mb-3 px-1 text-[11px] font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">
            Kesiapan Operasional Hari Ini
        </p>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">

            {{-- Card 1 — MP Readiness --}}
            @php
                $mpFit  = $kesiapan['mp_fit'];
                $mpNeed = $kesiapan['mp_need'];
                if ($mpFit === null) {
                    // Belum ada sesi briefing hari ini — tidak ada angka untuk ditampilkan.
                    $mpValue  = null;
                    $mpStatus = 'Menunggu Briefing';
                    $mpUnit   = null;
                    $mpSub    = 'Belum ada sesi briefing hari ini';
                    $mpNumCls = 'text-gray-400 dark:text-gray-500';
                } else {
                    $mpReady  = $mpFit >= $mpNeed;
                    $mpValue  = "{$mpFit} / {$mpNeed}";
                    $mpStatus = null;
                    $mpUnit   = 'MP';
                    $mpSub    = $mpReady ? 'MP Hadir — Siap' : 'MP Hadir — Belum Cukup';
                    $mpNumCls = $mpReady ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400';
                }
            @endphp
            <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 transition-shadow duration-200 hover:shadow-md dark:bg-gray-900 dark:ring-white/10">
                <div class="flex items-center gap-1.5">
                    <x-heroicon-o-users class="h-3.5 w-3.5 shrink-0 text-gray-300 dark:text-gray-600" />
                    <span class="text-[11px] font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">
                        MP Readiness
                    </span>
                </div>
                <p class="mt-3 flex items-baseline gap-1.5">
                    @if ($mpValue !== null)
                        <span class="text-3xl font-bold tabular-nums tracking-tight {{ $mpNumCls }}">{{ $mpValue }}</span>
                        <span class="text-sm font-medium text-gray-400 dark:text-gray-500">{{ $mpUnit }}</span>
                    @else
                        <span class="text-lg font-semibold {{ $mpNumCls }}">{{ $mpStatus }}</span>
                    @endif
                </p>
                <p class="mt-1.5 text-xs text-gray-400 dark:text-gray-500">{{ $mpSub }}</p>
            </div>

            {{-- Card 2 — Container Readiness --}}
            @php
                $cAvailKes = $kesiapan['container_available'];
                $cReady    = $kesiapan['container_ready'];
                if ($cAvailKes === null) {
                    // Belum ada baris container readiness untuk hari ini.
                    $cValue   = null;
                    $cStatus  = 'Belum Diinput';
                    $cUnit    = null;
                    $cSub     = 'Belum ada input container hari ini';
                    $cNumCls  = 'text-gray-400 dark:text-gray-500';
                } elseif ($cReady) {
                    $cValue   = $cAvailKes;
                    $cStatus  = null;
                    $cUnit    = 'Container';
                    $cSub     = 'Container Ready';
                    $cNumCls  = 'text-emerald-600 dark:text-emerald-400';
                } else {
                    $cValue   = $cAvailKes;
                    $cStatus  = null;
                    $cUnit    = 'Container';
                    $cSub     = 'Container — Belum Cukup';
                    $cNumCls  = 'text-rose-600 dark:text-rose-400';
                }
            @endphp
            <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 transition-shadow duration-200 hover:shadow-md dark:bg-gray-900 dark:ring-white/10">
                <div class="flex items-center gap-1.5">
                    <x-heroicon-o-archive-box class="h-3.5 w-3.5 shrink-0 text-gray-300 dark:text-gray-600" />
                    <span class="text-[11px] font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">
                        Container Readiness
                    </span>
                </div>
                <p class="mt-3 flex items-baseline gap-1.5">
                    @if ($cValue !== null)
                        <span class="text-3xl font-bold tabular-nums tracking-tight {{ $cNumCls }}">{{ $cValue }}</span>
                        <span class="text-sm font-medium text-gray-400 dark:text-gray-500">{{ $cUnit }}</span>
                    @else
                        <span class="text-lg font-semibold {{ $cNumCls }}">{{ $cStatus }}</span>
                    @endif
                </p>
                <p class="mt-1.5 text-xs text-gray-400 dark:text-gray-500">{{ $cSub }}</p>
            </div>

        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════════
         SECTION 3 — AKTIVITAS HARI INI
         4 KPI: Handover Hari Ini · Ready Loading · Loading Hari Ini · Bermasalah
    ══════════════════════════════════════════════════════════════════════ --}}
    @php $kpi = $this->getTodayActivityKpis(); @endphp
    <div class="mb-8">
        <p class="mb-3 px-1 text-[11px] font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">
            Aktivitas Hari Ini
        </p>

        <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">

            {{-- A — Handover Hari Ini --}}
            <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 transition-shadow duration-200 hover:shadow-md dark:bg-gray-900 dark:ring-white/10">
                <div class="flex items-center gap-1.5">
                    <x-heroicon-o-arrow-down-tray class="h-3.5 w-3.5 shrink-0 text-gray-300 dark:text-gray-600" />
                    <span class="text-[11px] font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">
                        Handover Hari Ini
                    </span>
                </div>
                <p class="mt-3 text-3xl font-bold tabular-nums tracking-tight text-gray-900 dark:text-white">
                    {{ $kpi['handover_today'] }}
                </p>
                <p class="mt-1.5 text-xs text-gray-400 dark:text-gray-500">unit masuk depot hari ini</p>
            </div>

            {{-- B — Ready Loading --}}
            <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 transition-shadow duration-200 hover:shadow-md dark:bg-gray-900 dark:ring-white/10">
                <div class="flex items-center gap-1.5">
                    <x-heroicon-o-check-circle class="h-3.5 w-3.5 shrink-0 text-gray-300 dark:text-gray-600" />
                    <span class="text-[11px] font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">
                        Ready Loading
                    </span>
                </div>
                <p class="mt-3 text-3xl font-bold tabular-nums tracking-tight {{ $kpi['ready_loading'] > 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-300 dark:text-gray-600' }}">
                    {{ $kpi['ready_loading'] }}
                </p>
                <p class="mt-1.5 text-xs text-gray-400 dark:text-gray-500">unit lolos seluruh requirement</p>
            </div>

            {{-- C — Loading Hari Ini --}}
            <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 transition-shadow duration-200 hover:shadow-md dark:bg-gray-900 dark:ring-white/10">
                <div class="flex items-center gap-1.5">
                    <x-heroicon-o-truck class="h-3.5 w-3.5 shrink-0 text-gray-300 dark:text-gray-600" />
                    <span class="text-[11px] font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">
                        Loading Hari Ini
                    </span>
                </div>
                <p class="mt-3 text-3xl font-bold tabular-nums tracking-tight {{ $kpi['loading_today'] > 0 ? 'text-sky-600 dark:text-sky-400' : 'text-gray-300 dark:text-gray-600' }}">
                    {{ $kpi['loading_today'] }}
                </p>
                <p class="mt-1.5 text-xs text-gray-400 dark:text-gray-500">unit masuk proses loading hari ini</p>
            </div>

            {{-- D — Bermasalah --}}
            <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 transition-shadow duration-200 hover:shadow-md
                        {{ $kpi['problematic_today'] > 0 ? 'ring-rose-200 dark:ring-rose-900/40' : '' }}
                        dark:bg-gray-900 dark:ring-white/10">
                <div class="flex items-center gap-1.5">
                    <x-heroicon-o-exclamation-circle class="h-3.5 w-3.5 shrink-0 text-gray-300 dark:text-gray-600" />
                    <span class="text-[11px] font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">
                        Bermasalah
                    </span>
                </div>
                <p class="mt-3 text-3xl font-bold tabular-nums tracking-tight {{ $kpi['problematic_today'] > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-gray-300 dark:text-gray-600' }}">
                    {{ $kpi['problematic_today'] }}
                </p>
                <p class="mt-1.5 text-xs text-gray-400 dark:text-gray-500">unit return to PDC hari ini</p>
            </div>

        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════════
         SECTION 4 — PERLU PERHATIAN
         Exception monitoring — selalu tampil, bukan conditional.
         FC harus tahu kondisi depot: merah jika ada masalah, hijau jika aman.
    ══════════════════════════════════════════════════════════════════════ --}}
    <div class="mb-8">
        <p class="mb-3 px-1 text-[11px] font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">
            Perlu Perhatian
        </p>

        {{-- Kedua kartu memakai satu blok markup identik (varian dihitung di awal)
             agar padding, alignment, dan rhythm-nya tidak bisa melenceng. --}}
        @php
            $perhatianCards = [
                [
                    'label'  => 'Shipment Bermasalah',
                    'count'  => $perhatian['bermasalah'],
                    'icon'   => $perhatian['bermasalah'] > 0 ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle',
                    'numCls' => $perhatian['bermasalah'] > 0
                        ? 'text-rose-600 dark:text-rose-400'
                        : 'text-emerald-600 dark:text-emerald-400',
                    'ring'   => $perhatian['bermasalah'] > 0
                        ? 'ring-rose-200 dark:ring-rose-900/50'
                        : 'ring-gray-950/5 dark:ring-white/10',
                    'sub'    => $perhatian['bermasalah'] > 0
                        ? 'Ada unit Return to PDC'
                        : 'Tidak ada unit bermasalah',
                ],
                [
                    'label'  => 'Shipment Tertahan',
                    'count'  => $perhatian['tertahan'],
                    'icon'   => $perhatian['tertahan'] > 0 ? 'heroicon-o-pause-circle' : 'heroicon-o-check-circle',
                    'numCls' => $perhatian['tertahan'] > 0
                        ? 'text-amber-600 dark:text-amber-400'
                        : 'text-emerald-600 dark:text-emerald-400',
                    'ring'   => $perhatian['tertahan'] > 0
                        ? 'ring-amber-200 dark:ring-amber-900/50'
                        : 'ring-gray-950/5 dark:ring-white/10',
                    'sub'    => $perhatian['tertahan'] > 0
                        ? 'Track requirement belum selesai'
                        : 'Tidak ada shipment tertahan',
                ],
            ];
        @endphp

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            @foreach ($perhatianCards as $card)
                <div class="rounded-xl bg-white p-6 shadow-sm ring-1 {{ $card['ring'] }} transition-shadow duration-200 hover:shadow-md dark:bg-gray-900">
                    <div class="flex items-center gap-1.5">
                        <x-dynamic-component :component="$card['icon']" class="h-3.5 w-3.5 shrink-0 text-gray-300 dark:text-gray-600" />
                        <span class="text-[11px] font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">
                            {{ $card['label'] }}
                        </span>
                    </div>
                    <p class="mt-3 flex items-baseline gap-1.5">
                        <span class="text-3xl font-bold tabular-nums tracking-tight {{ $card['numCls'] }}">{{ $card['count'] }}</span>
                        <span class="text-sm font-medium text-gray-400 dark:text-gray-500">Shipment</span>
                    </p>
                    <p class="mt-1.5 text-xs text-gray-400 dark:text-gray-500">{{ $card['sub'] }}</p>
                </div>
            @endforeach
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════════
         SECTION 5 — UNIT AKTIF DI YARD
         Daftar unit yang masih dalam tanggung jawab depot asal.
         Track status: Pickup · Handover · Stuffing · DeliveryToPort · Stacking · UnitLoading
         Tidak termasuk OnShip dan seterusnya — unit sudah lepas dari depot.
         Diurutkan: latest_track_at DESC.
    ══════════════════════════════════════════════════════════════════════ --}}
    @php $yardUnits = $this->getActiveYardUnits(); @endphp
    <div class="mb-8">
        <div class="mb-3 flex items-center gap-2 px-1">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">
                Unit Aktif di Yard
            </span>
            @if (count($yardUnits) > 0)
                <span class="rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-medium tabular-nums text-gray-500 dark:bg-gray-800 dark:text-gray-400">
                    {{ count($yardUnits) }} unit
                </span>
            @endif
        </div>

        <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 bg-gray-50/70 dark:border-gray-800 dark:bg-gray-800/40">
                        <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">SJKB</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">Shipment</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">Unit</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">Status</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">Menunggu</th>
                        <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">Voyage</th>
                        <th class="px-4 py-2.5 text-right text-[11px] font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">Updated</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 dark:divide-gray-800/60">
                    @forelse ($yardUnits as $yu)
                        @php
                            // Badge + dot color per status — perjalanan dari abu-abu (awal) ke biru (loading).
                            $statusMeta = match($yu['status_key']) {
                                'pickup'           => ['badge' => 'bg-gray-100 text-gray-700 dark:bg-gray-700/60 dark:text-gray-300', 'dot' => 'bg-gray-400'],
                                'handover'         => ['badge' => 'bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400', 'dot' => 'bg-blue-500'],
                                'stuffing'         => ['badge' => 'bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400', 'dot' => 'bg-amber-500'],
                                'delivery_to_port' => ['badge' => 'bg-orange-50 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400', 'dot' => 'bg-orange-500'],
                                'stacking'         => ['badge' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300', 'dot' => 'bg-amber-600'],
                                'unit_loading'     => ['badge' => 'bg-sky-50 text-sky-700 dark:bg-sky-900/30 dark:text-sky-400', 'dot' => 'bg-sky-500'],
                                default            => ['badge' => 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400', 'dot' => 'bg-gray-300'],
                            };
                        @endphp
                        @php
                            $hasSjkb     = ! in_array($yu['sjkb_no'], ['—', '', null], true);
                            $shipmentUrl = \App\Filament\FC\Pages\OperationalTasks::getUrl() . '?tableSearch=' . urlencode($yu['shipment_code']);
                        @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40 transition-colors">
                            <td class="px-4 py-3">
                                @if ($hasSjkb)
                                    <span class="font-mono text-xs font-semibold text-gray-800 dark:text-gray-200">{{ $yu['sjkb_no'] }}</span>
                                @else
                                    <span class="inline-flex items-center gap-1 text-xs font-medium italic text-amber-600 dark:text-amber-400">
                                        <x-heroicon-m-pencil-square class="h-3 w-3" />
                                        Belum Input SJKB
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <a href="{{ $shipmentUrl }}"
                                   class="inline-block rounded bg-gray-100 px-1.5 py-0.5 font-mono text-xs font-medium text-primary-600 hover:bg-primary-50 hover:text-primary-700 dark:bg-gray-800 dark:text-primary-400 dark:hover:bg-primary-900/30 transition-colors">
                                    {{ $yu['shipment_code'] }}
                                </a>
                            </td>
                            <td class="px-4 py-3 text-xs font-medium text-gray-700 dark:text-gray-300">
                                {{ $yu['unit_label'] }}
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-[11px] font-semibold {{ $statusMeta['badge'] }}">
                                    <span class="h-1.5 w-1.5 rounded-full {{ $statusMeta['dot'] }}"></span>
                                    {{ $yu['status_label'] }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-500 dark:text-gray-400">
                                {{ $yu['next_requirement'] }}
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-500 dark:text-gray-400">
                                {{ $yu['voyage'] }}
                            </td>
                            <td class="px-4 py-3 text-right text-xs tabular-nums text-gray-400 dark:text-gray-500">
                                {{ $yu['updated_at'] }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center">
                                <div class="flex flex-col items-center gap-2">
                                    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-gray-50 dark:bg-gray-800">
                                        <x-heroicon-o-cube-transparent class="h-6 w-6 text-gray-300 dark:text-gray-600" />
                                    </div>
                                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                        Tidak ada unit aktif di yard saat ini
                                    </p>
                                    <p class="text-xs text-gray-400 dark:text-gray-500">
                                        Unit akan muncul di sini setelah penjemputan atau handover.
                                    </p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</x-filament-panels::page>
