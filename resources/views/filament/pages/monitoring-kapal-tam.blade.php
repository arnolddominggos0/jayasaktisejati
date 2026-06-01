<x-filament-panels::page>
    <div class="space-y-5">

        {{-- ═══════════════════════════════════════════════════════════════ --}}
        {{-- OPERATIONAL HEADER                                            --}}
        {{-- ═══════════════════════════════════════════════════════════════ --}}
        <div class="flex items-center justify-between">
            <div class="flex items-baseline gap-3">
                <h1 class="text-lg font-bold text-gray-900 tracking-tight">Monitoring Kapal TAM</h1>
                <span class="text-sm text-gray-400">—</span>
                <p class="text-sm text-gray-500 font-medium">
                    {{ \Illuminate\Support\Carbon::createFromFormat('Y-m', $period)->translatedFormat('F Y') }}</p>
            </div>

            <div class="flex items-center gap-2">
                <select wire:model.live="period"
                    class="rounded border-gray-200 text-xs font-medium focus:ring-0 focus:border-gray-300 py-1.5 pl-2 pr-6 bg-white">
                    @foreach ($monthOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>

                <input wire:model.live="search" placeholder="Cari kapal / voyage"
                    class="rounded border-gray-200 text-xs w-44 focus:ring-0 focus:border-gray-300 py-1.5 px-2.5 bg-white">
            </div>
        </div>


        {{-- ═══════════════════════════════════════════════════════════════ --}}
        {{-- OPERATIONAL SUMMARY STRIP                                     --}}
        {{-- Critical dominant, informational secondary, KPI tertiary      --}}
        {{-- ═══════════════════════════════════════════════════════════════ --}}
        @php
            $kpi = \App\Services\Operational\VoyageOperationalSnapshot::kpiSummary($rows);
        @endphp

        <div class="flex items-center gap-2 flex-wrap">
            @if ($kpi['delayed_count'])
                <div class="bg-red-50 border border-red-200 rounded px-2.5 py-1.5 shadow-sm">
                    <span class="text-[10px] text-red-700 font-bold uppercase tracking-wider">Delay</span>
                    <span class="ml-1.5 text-base font-bold text-red-800">{{ $kpi['delayed_count'] }}</span>
                </div>
            @endif

            @if ($kpi['sailing_count'])
                <div class="bg-blue-50/50 border border-blue-200/50 rounded px-2.5 py-1.5">
                    <span class="text-[10px] text-blue-700 font-semibold uppercase tracking-wider">Berlayar</span>
                    <span class="ml-1.5 text-base font-bold text-blue-800">{{ $kpi['sailing_count'] }}</span>
                </div>
            @endif

            @if ($kpi['completed_count'])
                <div class="bg-gray-50/60 border border-gray-200/60 rounded px-2.5 py-1.5">
                    <span class="text-[10px] text-gray-600 font-semibold uppercase tracking-wider">Selesai</span>
                    <span class="ml-1.5 text-base font-bold text-gray-700">{{ $kpi['completed_count'] }}</span>
                </div>
            @endif

            @if ($kpi['scheduled_count'])
                <div class="bg-white border border-gray-200/50 rounded px-2.5 py-1.5">
                    <span class="text-[10px] text-gray-500 font-semibold uppercase tracking-wider">Terjadwal</span>
                    <span class="ml-1.5 text-base font-bold text-gray-600">{{ $kpi['scheduled_count'] }}</span>
                </div>
            @endif

            <div class="w-px h-6 bg-gray-200 mx-0.5"></div>

            @if ($kpi['overdue_count'])
                <div class="bg-orange-50 border border-orange-200 rounded px-2.5 py-1.5 shadow-sm">
                    <span class="text-[10px] text-orange-700 font-bold uppercase tracking-wider">Lewat</span>
                    <span class="ml-1.5 text-base font-bold text-orange-800">{{ $kpi['overdue_count'] }}</span>
                </div>
            @endif

            <div class="w-px h-6 bg-gray-200 mx-0.5"></div>

            <div class="bg-white border border-gray-200/50 rounded px-2.5 py-1.5">
                <span class="text-[10px] text-gray-500 font-semibold uppercase tracking-wider">OTD</span>
                <span class="ml-1.5 text-base font-bold text-gray-700">{{ $kpi['otd_percent'] }}%</span>
            </div>

            <div class="bg-white border border-gray-200/50 rounded px-2.5 py-1.5">
                <span class="text-[10px] text-gray-500 font-semibold uppercase tracking-wider">OTA</span>
                <span class="ml-1.5 text-base font-bold text-gray-700">{{ $kpi['ota_percent'] }}%</span>
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
                            <div class="font-semibold">
                                {{ optional($selectedMilestone->milestone_date)->format('d M Y') }}</div>
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
                            <textarea wire:model="milestoneForm.note" rows="3" class="w-full rounded-lg border-gray-300 text-sm"></textarea>
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


        {{-- ═══════════════════════════════════════════════════════════════ --}}
        {{-- INLINE OPERATIONAL MODAL                                      --}}
        {{-- ═══════════════════════════════════════════════════════════════ --}}
        @if ($showInlineModal)
            <div class="fixed inset-0 bg-black/40 flex items-center justify-center z-50"
                wire:click.self="closeInlineModal">
                <div class="bg-white rounded-lg shadow-xl w-[340px] p-4">

                    {{-- Header --}}
                    <div class="flex justify-between items-center mb-3">
                        <div>
                            <h3 class="text-sm font-semibold text-gray-800">
                                @php
                                    $modalTitle = match ($inlineModalType) {
                                        'atb' => 'Mark ATB',
                                        'atd' => 'Mark ATD',
                                        'ata' => 'Mark ATA',
                                        'closing' => 'Mark Closing',
                                        'vessel_check' => 'Update Readiness',
                                        'delay_case' => 'Create Delay Case',
                                        default => 'Action',
                                    };
                                @endphp
                                {{ $modalTitle }}
                            </h3>
                            @php
                                $modalVessel = $inlineModalVoyageId
                                    ? \App\Models\Voyage::find($inlineModalVoyageId)?->vessel?->name
                                    : null;
                            @endphp
                            @if ($modalVessel)
                                <p class="text-[10px] text-gray-400">{{ $modalVessel }}</p>
                            @endif
                        </div>
                        <button wire:click="closeInlineModal"
                            class="text-gray-400 hover:text-gray-600 text-xs">✕</button>
                    </div>

                    {{-- Body --}}
                    <div class="space-y-3">
                        @if (in_array($inlineModalType, ['atb', 'atd', 'ata', 'closing']))
                            <div>
                                <label
                                    class="block text-[10px] font-semibold text-gray-500 uppercase tracking-wider mb-1">Tanggal
                                    & Waktu</label>
                                <input type="datetime-local" wire:model="inlineForm.datetime"
                                    class="w-full rounded border-gray-200 text-xs py-1.5 px-2 focus:ring-0 focus:border-gray-300">
                            </div>
                        @endif

                        @if ($inlineModalType === 'vessel_check')
                            <div>
                                <label
                                    class="block text-[10px] font-semibold text-gray-500 uppercase tracking-wider mb-1">Status</label>
                                <select wire:model="inlineForm.status"
                                    class="w-full rounded border-gray-200 text-xs py-1.5 px-2 focus:ring-0 focus:border-gray-300">
                                    <option value="on_schedule">On Schedule</option>
                                    <option value="potential_delay">Potential Delay</option>
                                </select>
                            </div>
                        @endif

                        @if ($inlineModalType === 'delay_case')
                            <div class="text-xs text-gray-600">
                                Buat kasus delay baru untuk voyage ini?
                            </div>
                        @endif

                        @if (in_array($inlineModalType, ['atb', 'atd', 'ata', 'closing', 'vessel_check']))
                            <div>
                                <label
                                    class="block text-[10px] font-semibold text-gray-500 uppercase tracking-wider mb-1">Catatan</label>
                                <textarea wire:model="inlineForm.note" rows="2"
                                    class="w-full rounded border-gray-200 text-xs py-1.5 px-2 focus:ring-0 focus:border-gray-300 resize-none"
                                    placeholder="Catatan operasional opsional..."></textarea>
                            </div>
                        @endif
                    </div>

                    {{-- Footer --}}
                    <div class="mt-4 flex justify-end gap-2">
                        <button wire:click="closeInlineModal"
                            class="px-3 py-1.5 border border-gray-200 rounded text-[11px] text-gray-600 hover:bg-gray-50 transition">Batal</button>
                        <button wire:click="saveInlineModal"
                            class="px-3 py-1.5 bg-gray-900 text-white rounded text-[11px] hover:bg-gray-800 transition">
                            @if ($inlineModalType === 'delay_case')
                                Create Case
                            @else
                                Save
                            @endif
                        </button>
                    </div>

                </div>
            </div>
        @endif

    </div>
</x-filament-panels::page>
