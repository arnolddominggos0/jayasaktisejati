@php
    use App\Enums\CargoType;
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

    // Determine if vehicle cargo (drives container grouping)
    $isVehicleCargo = ($shipment->cargo_type instanceof CargoType)
        ? $shipment->cargo_type === CargoType::Vehicle
        : $shipment->cargo_type === CargoType::Vehicle->value;

    // ── Stage-level summary (in-memory, no extra query) ──────────────────────
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

    // ── Container grouping (vehicle cargo only, in-memory) ────────────────────
    if ($isVehicleCargo) {
        $namedGroups     = $units->filter(fn($u) => ! blank($u->container_display))
                                 ->groupBy('container_display')
                                 ->sortKeys();
        $unassignedGroup = $units->filter(fn($u) => blank($u->container_display));
        // Named containers first, unassigned last
        $containerGroups = $namedGroups->merge(
            $unassignedGroup->isNotEmpty() ? ['__UNASSIGNED__' => $unassignedGroup] : []
        );
    } else {
        $containerGroups = null;
    }
@endphp

<div class="space-y-2">
    @if ($stageLabel)
        <p class="text-sm text-blue-600 dark:text-blue-400 font-medium">
            Tahap Aktif: {{ $stageLabel }}
        </p>
    @endif

    {{-- ── Inspection Summary Widget ─────────────────────────────────────── --}}
    @if ($stageSummary)
        <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
            <div class="rounded-lg bg-white px-4 py-3 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <p class="text-[10px] font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">Total Unit</p>
                <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ $stageSummary['total'] }}</p>
            </div>
            <div class="rounded-lg bg-white px-4 py-3 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <p class="text-[10px] font-semibold uppercase tracking-wider text-emerald-500 dark:text-emerald-400">Selesai</p>
                <p class="mt-1 text-2xl font-bold {{ $stageSummary['completed'] > 0 ? 'text-emerald-700 dark:text-emerald-400' : 'text-gray-400 dark:text-gray-500' }}">
                    {{ $stageSummary['completed'] }}
                </p>
            </div>
            <div class="rounded-lg bg-white px-4 py-3 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <p class="text-[10px] font-semibold uppercase tracking-wider text-sky-500 dark:text-sky-400">Ditandatangani</p>
                <p class="mt-1 text-2xl font-bold {{ $stageSummary['signed'] > 0 ? 'text-sky-700 dark:text-sky-400' : 'text-gray-400 dark:text-gray-500' }}">
                    {{ $stageSummary['signed'] }}
                </p>
            </div>
            <div class="rounded-lg bg-white px-4 py-3 shadow-sm ring-1 {{ $stageSummary['pending'] > 0 ? 'ring-amber-200 dark:ring-amber-900/40' : 'ring-gray-950/5' }} dark:bg-gray-900 dark:ring-white/10">
                <p class="text-[10px] font-semibold uppercase tracking-wider {{ $stageSummary['pending'] > 0 ? 'text-amber-500 dark:text-amber-400' : 'text-gray-400 dark:text-gray-500' }}">Pending</p>
                <p class="mt-1 text-2xl font-bold {{ $stageSummary['pending'] > 0 ? 'text-amber-700 dark:text-amber-400' : 'text-gray-400 dark:text-gray-500' }}">
                    {{ $stageSummary['pending'] }}
                </p>
            </div>
        </div>

        @if ($stageSummary['legacy'] > 0)
            <div class="flex items-start gap-2 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 dark:border-amber-900/50 dark:bg-amber-900/20">
                <x-heroicon-o-exclamation-triangle class="mt-0.5 h-4 w-4 shrink-0 text-amber-500 dark:text-amber-400" />
                <p class="text-xs text-amber-700 dark:text-amber-300">
                    {{ $stageSummary['legacy'] }} inspeksi lama tanpa signature ditemukan.
                </p>
            </div>
        @endif
    @endif

    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    {{-- VEHICLE CARGO: grouped by container                                   --}}
    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    @if ($isVehicleCargo && $containerGroups !== null)

        @if ($containerGroups->isEmpty())
            <div class="rounded-lg border border-gray-200 dark:border-gray-700 px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                Belum ada unit terdaftar.
            </div>
        @else
            @foreach ($containerGroups as $containerKey => $containerUnits)
                <div class="rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">

                    {{-- Container group header --}}
                    <div class="flex items-center gap-3 px-4 py-2 bg-gray-50 dark:bg-gray-800/60 border-b border-gray-200 dark:border-gray-700">
                        @if ($containerKey === '__UNASSIGNED__')
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded text-xs font-semibold bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">
                                <x-heroicon-o-exclamation-circle class="w-3.5 h-3.5" />
                                Belum Assigned
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded text-xs font-semibold font-mono bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300">
                                <x-heroicon-o-archive-box class="w-3.5 h-3.5" />
                                {{ $containerKey }}
                            </span>
                        @endif
                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ $containerUnits->count() }} unit</span>
                    </div>

                    {{-- Units in this container --}}
                    <div class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach ($containerUnits as $unit)
                            @include('filament.fc.shipments.partials.unit-card', [
                                'unit'        => $unit,
                                'shipment'    => $shipment,
                                'activeStage' => $activeStage,
                                'canEdit'     => $canEdit,
                                'totalStages' => $totalStages,
                            ])
                        @endforeach
                    </div>

                </div>
            @endforeach
        @endif

    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    {{-- GENERAL CARGO: flat list (no container grouping)                      --}}
    {{-- ══════════════════════════════════════════════════════════════════════ --}}
    @else

        <div class="divide-y divide-gray-200 dark:divide-gray-700 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
            @forelse ($units as $unit)
                @include('filament.fc.shipments.partials.unit-card', [
                    'unit'        => $unit,
                    'shipment'    => $shipment,
                    'activeStage' => $activeStage,
                    'canEdit'     => $canEdit,
                    'totalStages' => $totalStages,
                ])
            @empty
                <div class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                    Belum ada unit terdaftar.
                </div>
            @endforelse
        </div>

    @endif

</div>
