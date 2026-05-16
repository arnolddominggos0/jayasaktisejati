<x-filament-panels::page>
    <div class="space-y-4">

        {{-- ═══════════════════════════════════════════════════════════════ --}}
        {{-- COMPACT HEADER                                                --}}
        {{-- ═══════════════════════════════════════════════════════════════ --}}
        <div class="flex items-center justify-between -mx-2">
            <div class="flex items-center gap-3">
                <h1 class="text-base font-bold text-gray-900 tracking-tight">Monitoring Vessel</h1>
                <span class="text-xs text-gray-400">—</span>
                <p class="text-xs text-gray-500">{{ \Illuminate\Support\Carbon::createFromFormat('Y-m', $period)->translatedFormat('F Y') }}</p>
            </div>

            <div class="flex items-center gap-2 -my-1">
                <select wire:model.live="period"
                    class="rounded border-gray-200 text-xs font-medium focus:ring-0 focus:border-gray-300 py-1 pl-2 pr-6">
                    @foreach ($monthOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>

                <input wire:model.live="search" placeholder="Cari kapal / voyage"
                    class="rounded border-gray-200 text-xs w-40 focus:ring-0 focus:border-gray-300 py-1 px-2">
            </div>
        </div>


        {{-- ═══════════════════════════════════════════════════════════════ --}}
        {{-- COMPACT OPERATIONAL SUMMARY STRIP                          --}}
        {{-- Critical: Delayed + Overdue dominant                       --}}
        {{-- Informational: lighter tone                                --}}
        {{-- KPI: lightest, white bg                                   --}}
        {{-- ═══════════════════════════════════════════════════════════════ --}}
        @php
            $delayed = $rows->filter(
                fn($v) => $v->operational_status_enum === \App\Enums\VoyageOperationalStatus::DELAYED,
            );

            $sailing = $rows->filter(
                fn($v) => $v->operational_status_enum === \App\Enums\VoyageOperationalStatus::SAILING,
            );

            $completed = $rows->filter(
                fn($v) => $v->operational_status_enum === \App\Enums\VoyageOperationalStatus::COMPLETED,
            );

            $scheduled = $rows->filter(
                fn($v) => $v->operational_status_enum === \App\Enums\VoyageOperationalStatus::SCHEDULED,
            );

            $total = $rows->count();
            $otdOk = $rows->filter(fn($v) => $v->otd_status?->value === 'ontime')->count();
            $otaOk = $rows->filter(fn($v) => $v->ota_status?->value === 'ontime')->count();
            $overdueCount = $rows->sum(fn($v) => $v->milestones->where('is_overdue', true)->count());
        @endphp

        <div class="flex items-center gap-1 -my-1">
            @if ($delayed->count())
                <div class="bg-red-50 border border-red-200/70 rounded px-1.5 py-1">
                    <span class="text-[9px] text-red-700 font-semibold uppercase tracking-wide">Delayed</span>
                    <span class="ml-1 text-sm font-bold text-red-800">{{ $delayed->count() }}</span>
                </div>
            @endif

            @if ($sailing->count())
                <div class="bg-blue-50/40 border border-blue-100/50 rounded px-1.5 py-1">
                    <span class="text-[9px] text-blue-600/80 font-medium uppercase tracking-wide">Sailing</span>
                    <span class="ml-1 text-sm font-bold text-blue-700/80">{{ $sailing->count() }}</span>
                </div>
            @endif

            @if ($completed->count())
                <div class="bg-gray-50/40 border border-gray-100/50 rounded px-1.5 py-1">
                    <span class="text-[9px] text-gray-500/80 font-medium uppercase tracking-wide">Done</span>
                    <span class="ml-1 text-sm font-bold text-gray-600/80">{{ $completed->count() }}</span>
                </div>
            @endif

            @if ($scheduled->count())
                <div class="bg-gray-50/30 border border-gray-100/40 rounded px-1.5 py-1">
                    <span class="text-[9px] text-gray-400/80 font-medium uppercase tracking-wide">Sched</span>
                    <span class="ml-1 text-sm font-bold text-gray-500/80">{{ $scheduled->count() }}</span>
                </div>
            @endif

            <span class="text-gray-300 mx-0.5">|</span>

            @if ($overdueCount)
                <div class="bg-orange-50 border border-orange-200/70 rounded px-1.5 py-1">
                    <span class="text-[9px] text-orange-700 font-semibold uppercase tracking-wide">Overdue</span>
                    <span class="ml-1 text-sm font-bold text-orange-800">{{ $overdueCount }}</span>
                </div>
            @endif

            <span class="text-gray-300 mx-0.5">|</span>

            <div class="bg-white border border-gray-100/50 rounded px-1.5 py-1">
                <span class="text-[9px] text-gray-400 font-medium uppercase tracking-wide">OTD</span>
                <span class="ml-1 text-sm font-bold text-gray-600">{{ $total > 0 ? round(($otdOk / $total) * 100) : 0 }}%</span>
            </div>

            <div class="bg-white border border-gray-100/50 rounded px-1.5 py-1">
                <span class="text-[9px] text-gray-400 font-medium uppercase tracking-wide">OTA</span>
                <span class="ml-1 text-sm font-bold text-gray-600">{{ $total > 0 ? round(($otaOk / $total) * 100) : 0 }}%</span>
            </div>
        </div>


        {{-- ═══════════════════════════════════════════════════════════════ --}}
        {{-- OPERATIONAL MONITORING MATRIX (primary workspace)             --}}
        {{-- ═══════════════════════════════════════════════════════════════ --}}
        @include('filament.pages.partials.tam-matrix-view')


        {{-- ═══════════════════════════════════════════════════════════════ --}}
        {{-- OPERATIONAL CALENDAR (below matrix)                           --}}
        {{-- ═══════════════════════════════════════════════════════════════ --}}
        @if (count($calendar))
            @include('filament.pages.partials.tam-calendar')
        @endif


        {{-- ═══════════════════════════════════════════════════════════════ --}}
        {{-- MILESTONE MODAL                                               --}}
        {{-- ═══════════════════════════════════════════════════════════════ --}}
        @if ($showMilestoneModal && $selectedMilestone)
            <div class="fixed inset-0 bg-black/40 flex items-center justify-center z-50">
                <div class="bg-white rounded-xl shadow-xl w-[500px] p-6">

                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-lg font-semibold">
                            Detail Milestone {{ strtoupper($selectedMilestone->code) }}
                        </h2>
                        <button wire:click="$set('showMilestoneModal', false)"
                            class="text-gray-500 hover:text-gray-700">✕</button>
                    </div>

                    <div class="space-y-3 text-sm">
                        <div>
                            <div class="text-gray-500">Voyage</div>
                            <div class="font-semibold">{{ $selectedMilestone->voyage->voyage_no }}</div>
                        </div>
                        <div>
                            <div class="text-gray-500">Kapal</div>
                            <div class="font-semibold">{{ $selectedMilestone->voyage->vessel?->name }}</div>
                        </div>
                        <div>
                            <div class="text-gray-500">Pelabuhan</div>
                            <div class="font-semibold">{{ $selectedMilestone->port?->name ?? '-' }}</div>
                        </div>
                        <div>
                            <div class="text-gray-500">Tanggal Milestone</div>
                            <div class="font-semibold">{{ optional($selectedMilestone->milestone_date)->format('d M Y') }}</div>
                        </div>
                    </div>

                    <div class="border-t pt-4 mt-4 space-y-3 text-sm">
                        <div>
                            <div class="text-gray-500">Tanggal Dilaporkan</div>
                            <input type="date" wire:model="milestoneForm.actual_date"
                                class="w-full rounded-lg border-gray-300 text-sm">
                        </div>
                        <div>
                            <div class="text-gray-500">Kecepatan Kapal</div>
                            <input type="number" step="0.1" wire:model="milestoneForm.speed_knots"
                                class="w-full rounded-lg border-gray-300 text-sm">
                        </div>
                        <div>
                            <div class="text-gray-500">Catatan Monitoring</div>
                            <textarea wire:model="milestoneForm.note" rows="3"
                                class="w-full rounded-lg border-gray-300 text-sm"></textarea>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end gap-2">
                        <button wire:click="$set('showMilestoneModal', false)"
                            class="px-4 py-2 border rounded-lg text-sm">Batal</button>
                        <button wire:click="saveMilestone"
                            class="px-4 py-2 bg-gray-900 text-white rounded-lg text-sm">Simpan</button>
                    </div>

                </div>
            </div>
        @endif

    </div>
</x-filament-panels::page>
