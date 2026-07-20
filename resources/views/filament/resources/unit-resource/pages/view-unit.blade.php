@php
use App\Models\UnitInspection;

$unit = $this->record;
$s    = $unit->shipment;

// ── Inspections keyed by stage (eager-loaded) ─────────────────────────────
$inspections = $unit->inspections->keyBy('stage');

// ── Stage order ───────────────────────────────────────────────────────────
$stages = UnitInspection::STAGES;  // ['pickup','handover_depot','loading','unloading','selfdrive','dooring']
$stageLabels = UnitInspection::STAGE_LABELS;

$stageIcons = [
    'pickup'         => '🚛',
    'handover_depot' => '🏭',
    'loading'        => '📦',
    'unloading'      => '📬',
    'selfdrive'      => '🚗',
    'dooring'        => '🏠',
];

// ── Quality summary ───────────────────────────────────────────────────────
$totalStages    = count($stages);
$doneCount      = $inspections->count();
$failedCount    = $inspections->filter(fn ($i) => $i->status === 'failed')->count();
$totalItems     = $inspections->sum(fn ($i) => $i->items->count());
$totalNg        = $inspections->sum(fn ($i) => $i->items->where('result', 'ng')->count());

$qualityStatus = match(true) {
    $doneCount === 0    => 'unstarted',
    $failedCount > 0    => 'failed',
    $doneCount < $totalStages => 'partial',
    default             => 'passed',
};

$qualityBadge = match($qualityStatus) {
    'passed'    => ['label' => 'LULUS', 'bg' => 'bg-emerald-100 text-emerald-700 border-emerald-200'],
    'failed'    => ['label' => 'ADA TEMUAN', 'bg' => 'bg-red-100 text-red-700 border-red-200'],
    'partial'   => ['label' => 'SEBAGIAN', 'bg' => 'bg-amber-100 text-amber-700 border-amber-200'],
    default     => ['label' => 'BELUM DIPERIKSA', 'bg' => 'bg-gray-100 text-gray-500 border-gray-200'],
};
@endphp

<x-filament-panels::page>
<div class="max-w-5xl mx-auto space-y-5">

    {{-- ── Page header ──────────────────────────────────────────────────── --}}
    <div class="flex items-start justify-between gap-4">
        <div>
            <div class="text-xs text-gray-400 uppercase tracking-wider font-semibold">
                Detail Unit
            </div>
            <h1 class="mt-1 text-2xl font-bold text-gray-900 font-mono">
                {{ $unit->chassis_no }}
            </h1>
            <div class="mt-1 flex items-center gap-2 flex-wrap">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border
                    bg-primary-50 text-primary-700 border-primary-200">
                    {{ $unit->model_no }}
                </span>
                @if($unit->color)
                <span class="text-sm text-gray-500">{{ $unit->color }}</span>
                @endif
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border
                    {{ $qualityBadge['bg'] }}">
                    {{ $qualityBadge['label'] }}
                </span>
            </div>
        </div>

        {{-- Shipment quick info --}}
        @if($s)
        <div class="shrink-0 text-right">
            <div class="text-xs text-gray-400 uppercase tracking-wider">Shipment</div>
            <div class="mt-0.5 font-semibold text-gray-800 font-mono text-sm">{{ $s->code }}</div>
            <div class="text-xs text-gray-500 mt-0.5">
                {{ $s->vessel_name ?? '—' }} · {{ $s->pol ?? '—' }} → {{ $s->pod ?? '—' }}
            </div>
        </div>
        @endif
    </div>

    {{-- ── Tab Navigation ───────────────────────────────────────────────── --}}
    <div
        x-data="{ tab: new URLSearchParams(window.location.search).get('tab') || 'identitas' }"
        x-init="$watch('tab', v => {
            const u = new URL(window.location);
            u.searchParams.set('tab', v);
            window.history.replaceState({}, '', u);
        })"
    >
        {{-- Tab bar --}}
        <div class="flex items-center gap-1 border-b border-gray-200 mb-5">
            <button
                @click="tab = 'identitas'"
                :class="tab === 'identitas'
                    ? 'border-b-2 border-primary-600 text-primary-700 font-semibold'
                    : 'text-gray-500 hover:text-gray-700'"
                class="px-4 py-2.5 text-sm transition-colors -mb-px">
                Identitas Unit
            </button>
            <button
                @click="tab = 'checksheet'"
                :class="tab === 'checksheet'
                    ? 'border-b-2 border-primary-600 text-primary-700 font-semibold'
                    : 'text-gray-500 hover:text-gray-700'"
                class="px-4 py-2.5 text-sm transition-colors -mb-px flex items-center gap-1.5">
                Checksheet Perjalanan
                @if($doneCount > 0)
                <span class="inline-flex items-center justify-center w-4 h-4 rounded-full text-[10px] font-bold
                    {{ $failedCount > 0 ? 'bg-red-500 text-white' : 'bg-emerald-500 text-white' }}">
                    {{ $doneCount }}
                </span>
                @endif
            </button>
        </div>

        {{-- TAB 1 — Identitas Unit --}}
        <div x-show="tab === 'identitas'" x-cloak class="space-y-4">

            {{-- Unit identity card --}}
            <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-5">
                <div class="text-[11px] uppercase tracking-wider font-bold text-gray-400 mb-4">
                    Data Kendaraan
                </div>
                <div class="grid grid-cols-2 gap-x-8 gap-y-3 text-sm">
                    <div>
                        <div class="text-[11px] text-gray-400 uppercase tracking-wide mb-0.5">Model</div>
                        <div class="font-semibold text-gray-800">{{ $unit->model_no ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="text-[11px] text-gray-400 uppercase tracking-wide mb-0.5">Warna</div>
                        <div class="font-medium text-gray-700">{{ $unit->color ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="text-[11px] text-gray-400 uppercase tracking-wide mb-0.5">Chassis Number</div>
                        <div class="font-mono font-bold text-gray-900 text-[13px] tracking-wide">
                            {{ $unit->chassis_no ?? '—' }}
                        </div>
                    </div>
                    <div>
                        <div class="text-[11px] text-gray-400 uppercase tracking-wide mb-0.5">Engine Number</div>
                        <div class="font-mono font-medium text-gray-700 text-[13px]">
                            {{ $unit->engine_no ?? '—' }}
                        </div>
                    </div>
                    <div>
                        <div class="text-[11px] text-gray-400 uppercase tracking-wide mb-0.5">No. Polisi</div>
                        <div class="font-medium text-gray-700">{{ $unit->reg_no ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="text-[11px] text-gray-400 uppercase tracking-wide mb-0.5">SJKB No</div>
                        <div class="font-medium text-gray-700">{{ $unit->sjkb_no ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="text-[11px] text-gray-400 uppercase tracking-wide mb-0.5">DO Number</div>
                        <div class="font-medium text-gray-700">{{ $unit->do_number ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="text-[11px] text-gray-400 uppercase tracking-wide mb-0.5">Container</div>
                        <div class="font-mono text-gray-700 text-[12px]">{{ $unit->container_display ?? '—' }}</div>
                    </div>
                </div>

                @if($unit->notes)
                <div class="mt-4 pt-4 border-t border-gray-100">
                    <div class="text-[11px] text-gray-400 uppercase tracking-wide mb-1">Catatan</div>
                    <div class="text-sm text-gray-600">{{ $unit->notes }}</div>
                </div>
                @endif
            </div>

            {{-- Shipment / Perjalanan card --}}
            @if($s)
            <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-5">
                <div class="text-[11px] uppercase tracking-wider font-bold text-gray-400 mb-4">
                    Informasi Pengiriman
                </div>

                <div class="grid grid-cols-2 gap-x-8 gap-y-3 text-sm">
                    <div>
                        <div class="text-[11px] text-gray-400 uppercase tracking-wide mb-0.5">Kode Shipment</div>
                        <div class="font-mono font-semibold text-primary-700">{{ $s->code }}</div>
                    </div>
                    <div>
                        <div class="text-[11px] text-gray-400 uppercase tracking-wide mb-0.5">Status</div>
                        @php
                        $statusVal = is_object($s->status) ? $s->status->value : $s->status;
                        $statusBg = match($statusVal) {
                            'delivered' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
                            'transit'   => 'bg-blue-100 text-blue-700 border-blue-200',
                            'pickup'    => 'bg-amber-100 text-amber-700 border-amber-200',
                            default     => 'bg-gray-100 text-gray-600 border-gray-200',
                        };
                        @endphp
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold border {{ $statusBg }}">
                            {{ strtoupper($statusVal) }}
                        </span>
                    </div>
                    <div>
                        <div class="text-[11px] text-gray-400 uppercase tracking-wide mb-0.5">Kapal</div>
                        <div class="font-semibold text-gray-800">{{ $s->vessel_name ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="text-[11px] text-gray-400 uppercase tracking-wide mb-0.5">Voyage No</div>
                        <div class="font-mono text-gray-700">{{ $s->voyage ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="text-[11px] text-gray-400 uppercase tracking-wide mb-0.5">Port Muat (POL)</div>
                        <div class="font-medium text-gray-700">{{ $s->pol ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="text-[11px] text-gray-400 uppercase tracking-wide mb-0.5">Port Bongkar (POD)</div>
                        <div class="font-medium text-gray-700">{{ $s->pod ?? '—' }}</div>
                    </div>
                    @if($s->container_no)
                    <div>
                        <div class="text-[11px] text-gray-400 uppercase tracking-wide mb-0.5">Container No</div>
                        <div class="font-mono text-gray-700 text-[12px]">{{ $s->container_no }}</div>
                    </div>
                    @endif
                    @if($s->seal_no)
                    <div>
                        <div class="text-[11px] text-gray-400 uppercase tracking-wide mb-0.5">Seal No</div>
                        <div class="font-mono text-gray-700 text-[12px]">{{ $s->seal_no }}</div>
                    </div>
                    @endif
                </div>

                {{-- Timeline tanggal perjalanan --}}
                <div class="mt-5 pt-4 border-t border-gray-100">
                    <div class="text-[11px] uppercase tracking-wider font-bold text-gray-400 mb-3">
                        Timeline Tanggal
                    </div>
                    <div class="flex items-start gap-0 relative">
                        @php
                        $timeline = [
                            ['label' => 'Pickup',    'date' => $s->pickup_date,   'icon' => '🚛', 'key' => 'pickup'],
                            ['label' => 'ETD',       'date' => $s->etd,           'icon' => '⚓', 'key' => 'etd'],
                            ['label' => 'Onboard',   'date' => $s->onboard_at,    'icon' => '🚢', 'key' => 'onboard'],
                            ['label' => 'ETA',       'date' => $s->eta,           'icon' => '📍', 'key' => 'eta'],
                            ['label' => 'Tiba',      'date' => $s->arrived_at,    'icon' => '🏁', 'key' => 'arrived'],
                            ['label' => 'Delivered', 'date' => $s->delivered_at,  'icon' => '✅', 'key' => 'delivered'],
                        ];
                        @endphp
                        <div class="grid grid-cols-3 gap-3 w-full sm:grid-cols-6">
                            @foreach($timeline as $t)
                            @php
                            $dt = $t['date'];
                            if (is_string($dt)) { try { $dt = \Carbon\Carbon::parse($dt); } catch (\Throwable $e) { $dt = null; } }
                            $hasDt = $dt instanceof \Carbon\Carbon;
                            @endphp
                            <div class="flex flex-col items-center text-center">
                                <div class="text-lg leading-none mb-1">{{ $t['icon'] }}</div>
                                <div class="text-[10px] font-bold uppercase text-gray-400 mb-0.5">{{ $t['label'] }}</div>
                                @if($hasDt)
                                <div class="text-[11px] font-semibold text-gray-700">
                                    {{ $dt->format('d M Y') }}
                                </div>
                                @else
                                <div class="text-[11px] text-gray-300">—</div>
                                @endif
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
            @endif

        </div>
        {{-- /tab:identitas --}}

        {{-- TAB 2 — Checksheet Perjalanan --}}
        <div x-show="tab === 'checksheet'" x-cloak class="space-y-4">

            {{-- Quality summary strip --}}
            <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-4">
                <div class="flex items-center justify-between flex-wrap gap-3">
                    <div class="flex items-center gap-4">
                        {{-- Stage progress --}}
                        <div class="text-center">
                            <div class="text-2xl font-black {{ $doneCount === $totalStages ? 'text-emerald-600' : ($doneCount > 0 ? 'text-amber-600' : 'text-gray-300') }}">
                                {{ $doneCount }}<span class="text-sm font-normal text-gray-400">/{{ $totalStages }}</span>
                            </div>
                            <div class="text-[10px] uppercase text-gray-400 tracking-wider">Tahap</div>
                        </div>
                        <div class="w-px h-8 bg-gray-200"></div>
                        {{-- Items --}}
                        <div class="text-center">
                            <div class="text-2xl font-black text-gray-700">{{ $totalItems }}</div>
                            <div class="text-[10px] uppercase text-gray-400 tracking-wider">Item</div>
                        </div>
                        <div class="w-px h-8 bg-gray-200"></div>
                        {{-- NG count --}}
                        <div class="text-center">
                            <div class="text-2xl font-black {{ $totalNg > 0 ? 'text-red-600' : 'text-gray-300' }}">
                                {{ $totalNg }}
                            </div>
                            <div class="text-[10px] uppercase text-gray-400 tracking-wider">NG</div>
                        </div>
                    </div>

                    {{-- Stage pill progress --}}
                    <div class="flex items-center gap-1 flex-wrap">
                        @foreach($stages as $stg)
                        @php
                        $insp = $inspections->get($stg);
                        $pillBg = $insp
                            ? ($insp->status === 'passed' ? 'bg-emerald-100 text-emerald-700 border-emerald-300' : 'bg-red-100 text-red-700 border-red-300')
                            : 'bg-gray-100 text-gray-400 border-gray-200';
                        @endphp
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-medium border {{ $pillBg }}">
                            {{ $stageIcons[$stg] ?? '○' }}
                            {{ collect(explode(' ', $stageLabels[$stg]))->first() }}
                        </span>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Empty state --}}
            @if($doneCount === 0)
            <div class="bg-white border border-dashed border-gray-200 rounded-2xl p-12 text-center">
                <div class="text-4xl mb-3">📋</div>
                <div class="text-base font-semibold text-gray-600 mb-1">Belum Ada Inspeksi</div>
                <div class="text-sm text-gray-400">
                    Unit <span class="font-mono font-semibold">{{ $unit->chassis_no }}</span> belum memiliki
                    data checksheet inspeksi.
                </div>
                <div class="mt-4 text-xs text-gray-400">
                    6 tahap inspeksi: Pickup → Handover Depo → Loading → Unloading → Selfdrive → Dooring
                </div>
            </div>
            @else

            {{-- Stage-by-stage inspection cards --}}
            @foreach($stages as $idx => $stg)
            @php
            $insp     = $inspections->get($stg);
            $label    = $stageLabels[$stg];
            $icon     = $stageIcons[$stg] ?? '•';
            $isDone   = $insp !== null;
            $isPassed = $isDone && $insp->status === 'passed';
            $isFailed = $isDone && $insp->status === 'failed';

            // Group items by category
            $itemsByCategory = $isDone
                ? $insp->items->groupBy('category')
                : collect();

            $ngCount = $isDone ? $insp->items->where('result', 'ng')->count() : 0;

            $cardBorder = $isDone
                ? ($isFailed ? 'border-red-200' : 'border-emerald-200')
                : 'border-gray-100 opacity-50';

            $statusBadge = $isDone
                ? ($isFailed
                    ? 'bg-red-100 text-red-700 border-red-200'
                    : 'bg-emerald-100 text-emerald-700 border-emerald-200')
                : 'bg-gray-100 text-gray-400 border-gray-200';
            $statusLabel = $isDone ? ($isFailed ? 'TIDAK LULUS' : 'LULUS') : 'BELUM';

            $sourceLabel = match($insp?->source) {
                'live'               => 'Live',
                'historical_import'  => 'Historis',
                default              => '—',
            };
            $sourceBg = $insp?->source === 'live'
                ? 'bg-blue-50 text-blue-600 border-blue-200'
                : 'bg-gray-50 text-gray-500 border-gray-200';
            @endphp

            <div class="bg-white border {{ $cardBorder }} rounded-2xl shadow-sm overflow-hidden">
                {{-- Stage header --}}
                <div class="flex items-center justify-between px-5 py-3.5 border-b {{ $isDone ? ($isFailed ? 'border-red-100 bg-red-50/30' : 'border-emerald-100 bg-emerald-50/30') : 'border-gray-100 bg-gray-50/50' }}">
                    <div class="flex items-center gap-3">
                        {{-- Step number --}}
                        <div class="flex items-center justify-center w-7 h-7 rounded-full text-[11px] font-bold
                            {{ $isDone ? ($isFailed ? 'bg-red-500 text-white' : 'bg-emerald-500 text-white') : 'bg-gray-200 text-gray-500' }}">
                            {{ $idx + 1 }}
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="text-base leading-none">{{ $icon }}</span>
                                <span class="font-semibold text-gray-800 text-sm">{{ $label }}</span>
                            </div>
                            @if($isDone && $insp->checked_at)
                            <div class="text-[11px] text-gray-400 mt-0.5">
                                {{ $insp->checked_at->format('d M Y') }}
                            </div>
                            @endif
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        @if($isDone)
                        {{-- Source --}}
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium border {{ $sourceBg }}">
                            {{ $sourceLabel }}
                        </span>
                        {{-- NG count if any --}}
                        @if($ngCount > 0)
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold border bg-red-100 text-red-700 border-red-200">
                            {{ $ngCount }} NG
                        </span>
                        @endif
                        @endif

                        {{-- Status badge --}}
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold border {{ $statusBadge }}">
                            {{ $statusLabel }}
                        </span>
                    </div>
                </div>

                @if($isDone)
                {{-- Items grouped by category --}}
                <div class="p-4">
                    @if($itemsByCategory->isEmpty())
                    <div class="text-sm text-gray-400 italic text-center py-3">
                        Tidak ada item pemeriksaan tercatat.
                    </div>
                    @else
                    <div class="space-y-3">
                        @foreach($itemsByCategory as $category => $items)
                        @php
                        $catNg = $items->where('result', 'ng')->count();
                        @endphp
                        <div>
                            <div class="flex items-center gap-2 mb-1.5">
                                <span class="text-[10px] uppercase tracking-wider font-bold text-gray-500">{{ $category }}</span>
                                @if($catNg > 0)
                                <span class="text-[10px] font-bold text-red-600">{{ $catNg }} NG</span>
                                @endif
                            </div>
                            <div class="grid grid-cols-1 gap-1">
                                @foreach($items as $item)
                                @php
                                $isNg = $item->result === 'ng';
                                $rowBg = $isNg ? 'bg-red-50 border-red-100' : 'bg-gray-50 border-gray-100';
                                $resultBg = $isNg
                                    ? 'bg-red-500 text-white'
                                    : 'bg-emerald-500 text-white';
                                @endphp
                                <div class="flex items-center justify-between px-3 py-1.5 rounded-lg border {{ $rowBg }}">
                                    <div class="text-sm text-gray-700">{{ $item->item_name }}</div>
                                    <div class="flex items-center gap-2">
                                        @if($item->notes)
                                        <div class="text-[11px] text-gray-400 italic max-w-[200px] truncate">
                                            {{ $item->notes }}
                                        </div>
                                        @endif
                                        <span class="inline-flex items-center justify-center w-8 h-5 rounded text-[10px] font-black {{ $resultBg }}">
                                            {{ strtoupper($item->result) }}
                                        </span>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @endif

                    @if($insp->notes)
                    <div class="mt-3 pt-3 border-t border-gray-100 text-[12px] text-gray-500 italic">
                        📝 {{ $insp->notes }}
                    </div>
                    @endif
                </div>
                @else
                {{-- Not yet inspected --}}
                <div class="px-5 py-3 text-sm text-gray-400 italic">
                    Belum dilakukan inspeksi pada tahap ini.
                </div>
                @endif
            </div>
            @endforeach

            @endif
            {{-- /if doneCount === 0 --}}

        </div>
        {{-- /tab:checksheet --}}

    </div>
    {{-- /x-data tabs --}}

</div>
</x-filament-panels::page>
