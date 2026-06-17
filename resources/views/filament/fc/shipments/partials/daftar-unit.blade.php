@php
    use App\Models\UnitInspection;
    use App\Filament\FC\Pages\InspectUnitPage;
    use App\Services\InspectionDraftAutoCreate;
    use App\Services\ShipmentOwnership;

    $shipment    = $getRecord();
    $units       = $shipment->units()->with('inspections')->get();
    $activeStage = null;

    $trackStatus = $shipment->currentTrackStatus();
    if ($trackStatus) {
        $activeStage = InspectionDraftAutoCreate::resolveStage($trackStatus);
    }

    $stageLabel  = $activeStage ? (UnitInspection::STAGE_LABELS[$activeStage] ?? $activeStage) : null;
    $canEdit     = ShipmentOwnership::canEdit(auth()->user(), $shipment);
@endphp

<div class="space-y-2">
    @if ($stageLabel)
        <p class="text-sm text-blue-600 dark:text-blue-400 font-medium">
            Tahap Aktif: {{ $stageLabel }}
        </p>
    @endif

    <div class="divide-y divide-gray-200 dark:divide-gray-700 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
        @forelse ($units as $unit)
            @php
                $inspection = $activeStage
                    ? $unit->inspections->firstWhere('stage', $activeStage)
                    : null;
                $isDone     = $inspection?->submitted_at !== null;
                $inspectUrl = $activeStage && $inspection
                    ? InspectUnitPage::getUrl(['record' => $shipment->getKey(), 'unit' => $unit->getKey()])
                    : null;
            @endphp
            <div class="flex items-center justify-between px-4 py-3 bg-white dark:bg-gray-900 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
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
                    </div>
                </div>

                <div class="ml-4 flex items-center gap-2 shrink-0">
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
                           class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium rounded-lg
                                  {{ $isDone
                                      ? 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600'
                                      : 'bg-amber-500 text-white hover:bg-amber-600 dark:bg-amber-600 dark:hover:bg-amber-700' }}
                                  transition-colors">
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
        @empty
            <div class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                Belum ada unit terdaftar.
            </div>
        @endforelse
    </div>
</div>
