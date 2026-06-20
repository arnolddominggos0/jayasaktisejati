@php
    use App\Models\UnitInspection;
    use App\Filament\FC\Pages\InspectUnitPage;
    use App\Services\InspectionDraftAutoCreate;
    use App\Services\ShipmentOwnership;

    $shipment    = $getRecord();
    $units       = $shipment->units()->with('inspections')->get();   // 2 queries total, no N+1
    $activeStage = null;

    $trackStatus = $shipment->currentTrackStatus();
    if ($trackStatus) {
        $activeStage = InspectionDraftAutoCreate::resolveStage($trackStatus);
    }

    $stageLabel  = $activeStage ? (UnitInspection::STAGE_LABELS[$activeStage] ?? $activeStage) : null;
    $canEdit     = ShipmentOwnership::canEdit(auth()->user(), $shipment);
    $totalStages = count(UnitInspection::STAGES);

    // ── Deliverable 4: stage-level summary (in-memory, no extra query) ────────
    $stageSummary = null;
    if ($activeStage) {
        $activeInspections = $units->flatMap(
            fn($u) => $u->inspections->filter(fn($i) => $i->stage === $activeStage)
        );
        $stageSummary = [
            'total'     => $units->count(),
            'completed' => $activeInspections->filter(fn($i) => $i->submitted_at !== null)->count(),
            'signed'    => $activeInspections->filter(fn($i) => $i->submitted_at !== null && $i->signature_path !== null)->count(),
            'pending'   => $activeInspections->filter(fn($i) => $i->submitted_at === null)->count(),
            'legacy'    => $activeInspections->filter(fn($i) => $i->submitted_at !== null && ! $i->signature_path)->count(),
        ];
    }
@endphp

<div class="space-y-2">
    @if ($stageLabel)
        <p class="text-sm text-blue-600 dark:text-blue-400 font-medium">
            Tahap Aktif: {{ $stageLabel }}
        </p>
    @endif

    {{-- ── Deliverable 4: Inspection Summary Widget ────────────────────────── --}}
    @if ($stageSummary)
        <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
            {{-- Total --}}
            <div class="rounded-lg bg-white px-4 py-3 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <p class="text-[10px] font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">Total Unit</p>
                <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ $stageSummary['total'] }}</p>
            </div>
            {{-- Completed --}}
            <div class="rounded-lg bg-white px-4 py-3 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <p class="text-[10px] font-semibold uppercase tracking-wider text-emerald-500 dark:text-emerald-400">Selesai</p>
                <p class="mt-1 text-2xl font-bold {{ $stageSummary['completed'] > 0 ? 'text-emerald-700 dark:text-emerald-400' : 'text-gray-400 dark:text-gray-500' }}">
                    {{ $stageSummary['completed'] }}
                </p>
            </div>
            {{-- Signed --}}
            <div class="rounded-lg bg-white px-4 py-3 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <p class="text-[10px] font-semibold uppercase tracking-wider text-sky-500 dark:text-sky-400">Ditandatangani</p>
                <p class="mt-1 text-2xl font-bold {{ $stageSummary['signed'] > 0 ? 'text-sky-700 dark:text-sky-400' : 'text-gray-400 dark:text-gray-500' }}">
                    {{ $stageSummary['signed'] }}
                </p>
            </div>
            {{-- Pending --}}
            <div class="rounded-lg bg-white px-4 py-3 shadow-sm ring-1 {{ $stageSummary['pending'] > 0 ? 'ring-amber-200 dark:ring-amber-900/40' : 'ring-gray-950/5' }} dark:bg-gray-900 dark:ring-white/10">
                <p class="text-[10px] font-semibold uppercase tracking-wider {{ $stageSummary['pending'] > 0 ? 'text-amber-500 dark:text-amber-400' : 'text-gray-400 dark:text-gray-500' }}">Pending</p>
                <p class="mt-1 text-2xl font-bold {{ $stageSummary['pending'] > 0 ? 'text-amber-700 dark:text-amber-400' : 'text-gray-400 dark:text-gray-500' }}">
                    {{ $stageSummary['pending'] }}
                </p>
            </div>
        </div>

        {{-- ── Deliverable 3: Legacy signature warning ─────────────────────── --}}
        @if ($stageSummary['legacy'] > 0)
            <div class="flex items-start gap-2 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 dark:border-amber-900/50 dark:bg-amber-900/20">
                <x-heroicon-o-exclamation-triangle class="mt-0.5 h-4 w-4 shrink-0 text-amber-500 dark:text-amber-400" />
                <p class="text-xs text-amber-700 dark:text-amber-300">
                    {{ $stageSummary['legacy'] }} inspeksi lama tanpa signature ditemukan.
                </p>
            </div>
        @endif
    @endif

    <div class="divide-y divide-gray-200 dark:divide-gray-700 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
        @forelse ($units as $unit)
            @php
                // All in-memory — no extra queries
                $inspByStage = $unit->inspections->keyBy('stage');
                $inspection  = $activeStage ? ($inspByStage[$activeStage] ?? null) : null;
                $isDone      = $inspection?->submitted_at !== null;
                $inspectUrl  = $activeStage && $inspection
                    ? InspectUnitPage::getUrl(['record' => $shipment->getKey(), 'unit' => $unit->getKey()])
                    : null;

                // Progress counts (Task 1)
                $passedCount    = $unit->inspections->where('status', UnitInspection::STATUS_PASSED)->count();
                $failedCount    = $unit->inspections->where('status', UnitInspection::STATUS_FAILED)->count();
                $submittedCount = $unit->inspections->filter(fn ($i) => $i->submitted_at !== null)->count();
                $pendingCount   = $totalStages - $submittedCount;

                // Latest submitted gate (Task 3)
                $latestSubmitted = $unit->inspections
                    ->filter(fn ($i) => $i->submitted_at !== null)
                    ->sortByDesc('submitted_at')
                    ->first();
            @endphp

            <div class="px-4 py-3 bg-white dark:bg-gray-900 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">

                {{-- Unit identifier row --}}
                <div class="flex items-start justify-between gap-3">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="font-mono text-sm font-semibold text-gray-900 dark:text-white">
                                {{ $unit->chassis_no ?? '—' }}
                            </span>
                            @if ($unit->model_no)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                                    {{ $unit->model_no }}
                                </span>
                            @endif
                            @if ($unit->reg_no)
                                <span class="text-xs text-gray-500">No. Pol: {{ $unit->reg_no }}</span>
                            @endif
                            @if ($unit->color)
                                <span class="text-xs text-gray-500">{{ $unit->color }}</span>
                            @endif
                            @if ($unit->engine_no)
                                <span class="text-xs text-gray-500">Mesin: <span class="font-mono">{{ $unit->engine_no }}</span></span>
                            @endif
                        </div>
                    </div>

                    {{-- Active stage action button --}}
                    <div class="flex items-center gap-2 shrink-0">
                        @if ($activeStage)
                            @if ($isDone)
                                <span class="inline-flex items-center gap-1 text-xs text-green-600 dark:text-green-400 font-medium">
                                    <x-heroicon-m-check-circle class="w-4 h-4" />
                                    Selesai
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 text-xs text-amber-600 dark:text-amber-400 font-medium">
                                    <x-heroicon-o-clock class="w-4 h-4" />
                                    Menunggu
                                </span>
                            @endif
                        @endif

                        @if ($inspectUrl && $canEdit)
                            <a href="{{ $inspectUrl }}"
                               class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium rounded-lg transition-colors
                                      {{ $isDone
                                          ? 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600'
                                          : 'bg-amber-500 text-white hover:bg-amber-600 dark:bg-amber-600 dark:hover:bg-amber-700' }}">
                                @if ($isDone)
                                    <x-heroicon-m-eye class="w-3.5 h-3.5" />
                                    Lihat
                                @else
                                    <x-heroicon-m-clipboard-document-check class="w-3.5 h-3.5" />
                                    Inspeksi
                                @endif
                            </a>
                        @endif
                    </div>
                </div>

                {{-- Task 1: Progress summary --}}
                <div class="mt-2 flex items-center gap-3 text-xs">
                    <span class="text-gray-600 dark:text-gray-400">
                        Selesai:
                        <span class="font-semibold text-gray-900 dark:text-white">{{ $submittedCount }} / {{ $totalStages }}</span>
                    </span>
                    <span class="text-gray-300 dark:text-gray-600">·</span>
                    <span class="text-green-700 dark:text-green-400">
                        Passed: <span class="font-semibold">{{ $passedCount }}</span>
                    </span>
                    @if ($failedCount > 0)
                        <span class="text-gray-300 dark:text-gray-600">·</span>
                        <span class="text-red-600 dark:text-red-400">
                            Failed: <span class="font-semibold">{{ $failedCount }}</span>
                        </span>
                    @endif
                    <span class="text-gray-300 dark:text-gray-600">·</span>
                    <span class="text-amber-600 dark:text-amber-400">
                        Pending: <span class="font-semibold">{{ $pendingCount }}</span>
                    </span>
                </div>

                {{-- Task 2: Stage chips --}}
                <div class="mt-2 flex flex-wrap gap-1.5">
                    @foreach (UnitInspection::STAGE_LABELS as $stageKey => $stageName)
                        @php
                            $stageInsp  = $inspByStage[$stageKey] ?? null;
                            $isActive   = $stageKey === $activeStage;

                            if (! $stageInsp) {
                                // Draft not created for this unit (unit added after skeleton)
                                $chipClass  = 'bg-gray-100 text-gray-400 dark:bg-gray-800 dark:text-gray-500';
                                $chipStatus = '—';
                            } elseif ($stageInsp->submitted_at === null) {
                                $chipClass  = 'bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400';
                                $chipStatus = 'Pending';
                            } elseif ($stageInsp->status === UnitInspection::STATUS_PASSED) {
                                $chipClass  = 'bg-green-50 text-green-700 dark:bg-green-900/30 dark:text-green-400';
                                $chipStatus = 'Passed';
                            } else {
                                $chipClass  = 'bg-red-50 text-red-700 dark:bg-red-900/30 dark:text-red-400';
                                $chipStatus = 'Failed';
                            }
                        @endphp
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-medium
                                     {{ $chipClass }}
                                     {{ $isActive ? 'ring-1 ring-inset ring-blue-400 dark:ring-blue-500' : '' }}">
                            <span class="text-[10px] opacity-75">{{ $stageName }}</span>
                            <span class="font-semibold">{{ $chipStatus }}</span>
                        </span>
                    @endforeach
                </div>

                {{-- Task 3: Latest gate decision --}}
                @if ($latestSubmitted?->gate_decision)
                    @php
                        $gateKey = $latestSubmitted->gate_decision;
                        $gateText = UnitInspection::GATE_LABELS[$gateKey] ?? $gateKey;
                        $gateClass = match ($gateKey) {
                            UnitInspection::GATE_ACCEPT            => 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300',
                            UnitInspection::GATE_ALLOW_WITH_REMARK => 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300',
                            UnitInspection::GATE_RETURN_TO_PDC     => 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300',
                            default                                 => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
                        };
                    @endphp
                    <div class="mt-2 flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400">
                        <span>Gate terakhir:</span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded font-semibold {{ $gateClass }}">
                            {{ $gateText }}
                        </span>
                        <span class="text-[10px] text-gray-400 dark:text-gray-500">
                            {{ $latestSubmitted->submitted_at->format('d M Y') }}
                        </span>
                    </div>
                @endif

                {{-- Deliverable 2: Active stage evidence (PIC + timestamp + status badge + PDF link) --}}
                @if ($isDone && $inspection)
                    <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-gray-500 dark:text-gray-400">
                        {{-- Status badge --}}
                        <span class="inline-flex items-center rounded px-1.5 py-0.5 text-[10px] font-semibold
                            {{ $inspection->status === UnitInspection::STATUS_PASSED
                                ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400'
                                : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' }}">
                            {{ strtoupper($inspection->status) }}
                        </span>
                        {{-- PIC name --}}
                        @if ($inspection->signed_by)
                            <span class="flex items-center gap-1">
                                <x-heroicon-o-user-circle class="h-3 w-3 shrink-0" />
                                {{ $inspection->signed_by }}
                            </span>
                        @else
                            <span class="italic text-amber-500 dark:text-amber-400">Tanpa tanda tangan</span>
                        @endif
                        {{-- Submission timestamp --}}
                        @if ($inspection->submitted_at)
                            <span class="flex items-center gap-1">
                                <x-heroicon-o-clock class="h-3 w-3 shrink-0" />
                                {{ $inspection->submitted_at->format('d M Y H:i') }}
                            </span>
                        @endif
                        {{-- PDF download link --}}
                        @if ($inspection->pdf_path)
                            <a href="{{ asset('storage/' . $inspection->pdf_path) }}"
                               target="_blank"
                               class="ml-auto flex items-center gap-1 rounded px-2 py-0.5 text-[10px] font-semibold
                                      bg-primary-50 text-primary-700 hover:bg-primary-100
                                      dark:bg-primary-900/20 dark:text-primary-400 dark:hover:bg-primary-900/40
                                      transition-colors">
                                <x-heroicon-m-arrow-down-tray class="h-3 w-3 shrink-0" />
                                Download PDF
                            </a>
                        @endif
                    </div>
                @endif

            </div>
        @empty
            <div class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                Belum ada unit terdaftar.
            </div>
        @endforelse
    </div>
</div>
