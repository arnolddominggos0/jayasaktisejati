@php
    use App\Models\UnitInspection;
    use App\Filament\FC\Pages\InspectUnitPage;

    // Variables passed from daftar-unit: $unit, $shipment, $activeStage, $canEdit

    // All in-memory — no extra queries
    $inspByStage = $unit->inspections->keyBy('stage');
    $inspection  = $activeStage ? ($inspByStage[$activeStage] ?? null) : null;
    $isDone      = $inspection?->submitted_at !== null;
    $inspectUrl  = $activeStage && $inspection
        ? InspectUnitPage::getUrl(['record' => $shipment->getKey(), 'unit' => $unit->getKey()])
        : null;

    // Sprint UX-04 (Scope 1+2): progres HANYA sampai tahap aktif — ini
    // BUKAN penyederhanaan tampilan semata, ini mengikuti bentuk data
    // aslinya. InspectionDraftAutoCreate::resolveStage() hanya membuat baris
    // UnitInspection sampai tahap aktif; stage sesudahnya memang belum
    // pernah tercipta di database. Mengiterasi UnitInspection::STAGES penuh
    // (lama) berarti UI menampilkan slot yang secara data belum eksis.
    $activeIndex   = $activeStage ? array_search($activeStage, UnitInspection::STAGES, true) : false;
    $visibleStages = $activeIndex !== false
        ? array_slice(UnitInspection::STAGES, 0, $activeIndex + 1)
        : [];

    // Sprint UX-04 (Scope 5): alert operasional HARUS selalu terlihat dari
    // TAHAP MANA PUN — bukan hanya tahap aktif. Unit yang pernah Return to
    // PDC atau Gagal di tahap sebelumnya tetap butuh perhatian operator
    // terlepas di tahap mana ia sedang bekerja sekarang. allow_with_remark
    // SENGAJA tidak dianggap "masalah" di sini — gate itu tetap lolos,
    // hanya disertai catatan (detail lengkapnya ada di InspectUnitPage).
    $problemInspection = $unit->inspections->first(
        fn ($i) => $i->gate_decision === UnitInspection::GATE_RETURN_TO_PDC
            || $i->status === UnitInspection::STATUS_FAILED
    );
@endphp

<div class="px-4 py-3 bg-white dark:bg-gray-900 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">

    {{-- ── Alert operasional (Scope 5) — selalu terlihat, lintas tahap ───────── --}}
    @if ($problemInspection)
        <div class="mb-2 flex items-center gap-1.5 rounded-md bg-red-50 dark:bg-red-900/20 px-2.5 py-1.5 text-xs font-semibold text-red-700 dark:text-red-400">
            <x-heroicon-m-exclamation-triangle class="h-4 w-4 shrink-0" />
            <span>
                {{ UnitInspection::STAGE_LABELS[$problemInspection->stage] ?? $problemInspection->stage }} —
                {{ $problemInspection->gate_decision === UnitInspection::GATE_RETURN_TO_PDC ? 'Return to PDC' : 'Gagal Inspeksi' }}
            </span>
        </div>
    @endif

    {{-- ── Primary identifier row ──────────────────────────────────────────── --}}
    <div class="flex items-start justify-between gap-3">
        <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 flex-wrap">

                {{-- SJKB — primary operational identifier --}}
                <span class="font-mono text-sm font-semibold text-gray-900 dark:text-white">
                    {{ $unit->sjkb_no ?? '—' }}
                </span>

                {{-- Model --}}
                @if ($unit->model_no)
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                        {{ $unit->model_no }}
                    </span>
                @endif

                {{-- Color --}}
                @if ($unit->color)
                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ $unit->color }}</span>
                @endif

            </div>

            {{-- Secondary: chassis + engine (smaller, below SJKB) --}}
            <div class="mt-0.5 flex items-center gap-3 flex-wrap">
                @if ($unit->chassis_no)
                    <span class="text-[11px] text-gray-400 dark:text-gray-500 font-mono">
                        Rangka: {{ $unit->chassis_no }}
                    </span>
                @endif
                @if ($unit->engine_no)
                    <span class="text-[11px] text-gray-400 dark:text-gray-500 font-mono">
                        Mesin: {{ $unit->engine_no }}
                    </span>
                @endif
                @if ($unit->reg_no)
                    <span class="text-[11px] text-gray-400 dark:text-gray-500">
                        No. Pol: {{ $unit->reg_no }}
                    </span>
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

    {{-- ── Progress tahap (Sprint UX-04) — HANYA sampai tahap aktif ──────────── --}}
    {{-- Tahap yang belum relevan tidak dirender sama sekali (bukan "-", --}}
    {{-- bukan "Pending") — lihat $visibleStages di atas. --}}
    @if (! empty($visibleStages))
        <div class="mt-2 flex flex-wrap gap-1.5">
            @foreach ($visibleStages as $stageKey)
                @php
                    $stageInsp = $inspByStage[$stageKey] ?? null;
                    $isActive  = $stageKey === $activeStage;

                    if (! $stageInsp || $stageInsp->submitted_at === null) {
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
                    <span class="text-[10px] opacity-75">{{ UnitInspection::STAGE_LABELS[$stageKey] ?? $stageKey }}</span>
                    <span class="font-semibold">{{ $chipStatus }}</span>
                </span>
            @endforeach
        </div>
    @endif

</div>
