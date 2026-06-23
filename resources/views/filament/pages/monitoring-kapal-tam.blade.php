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
        {{-- CARRIER READINESS WIDGET                                      --}}
        {{-- Scope: SEMUA voyage H-2/H-1 — TANPA filter shipment          --}}
        {{-- Boundary: berbeda dari cargo monitoring grid di bawah         --}}
        {{-- ═══════════════════════════════════════════════════════════════ --}}
        @if (count($carrierReadiness))
        @php
            $crPending = collect($carrierReadiness)->where('status', 'pending')->count();
            $crOk      = collect($carrierReadiness)->where('status', 'ok')->count();
            $crLate    = collect($carrierReadiness)->where('status', 'late')->count();
        @endphp
        <div class="bg-white border border-gray-200 rounded-lg overflow-hidden shadow-sm">
            {{-- Header --}}
            <div class="flex items-center justify-between px-4 py-2.5 border-b border-gray-100 bg-gray-50/50">
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-amber-400"></span>
                    <h3 class="text-[11px] font-bold uppercase tracking-wider text-gray-600">
                        Carrier Readiness
                    </h3>
                    <span class="text-[9px] text-gray-400 font-medium">H-2 / H-1</span>
                </div>
                {{-- Summary badges --}}
                <div class="flex items-center gap-1.5">
                    @if ($crPending)
                        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[9px] font-semibold bg-gray-100 text-gray-600 border border-gray-200">
                            <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span>
                            Pending {{ $crPending }}
                        </span>
                    @endif
                    @if ($crOk)
                        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[9px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                            OK {{ $crOk }}
                        </span>
                    @endif
                    @if ($crLate)
                        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[9px] font-semibold bg-red-50 text-red-700 border border-red-200">
                            <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                            Late {{ $crLate }}
                        </span>
                    @endif
                </div>
            </div>

            {{-- Table --}}
            <table class="min-w-full text-[11px]">
                <thead class="bg-gray-50/30 text-gray-400 font-medium text-[10px] border-b border-gray-100">
                    <tr>
                        <th class="px-4 py-1.5 text-left w-12">H</th>
                        <th class="px-4 py-1.5 text-left">Kapal</th>
                        <th class="px-4 py-1.5 text-left">Voyage</th>
                        <th class="px-4 py-1.5 text-left">ETD</th>
                        <th class="px-4 py-1.5 text-center w-24">Status</th>
                        <th class="px-4 py-1.5 text-left">Alasan / Catatan</th>
                        <th class="px-4 py-1.5 text-center w-28">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach ($carrierReadiness as $cr)
                    <tr class="{{ $cr['status'] === 'late' ? 'bg-red-50/30' : '' }}">
                        {{-- H badge --}}
                        <td class="px-4 py-2">
                            <span class="inline-block text-[9px] font-bold px-1.5 py-0.5 rounded
                                {{ $cr['day_code'] === 'H-1' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700' }}">
                                {{ $cr['day_code'] }}
                            </span>
                        </td>
                        {{-- Vessel --}}
                        <td class="px-4 py-2 font-medium text-gray-800">{{ $cr['vessel_name'] }}</td>
                        {{-- Voyage no --}}
                        <td class="px-4 py-2 text-gray-500 font-mono">{{ $cr['voyage_no'] }}</td>
                        {{-- ETD --}}
                        <td class="px-4 py-2 text-gray-500">{{ $cr['etd'] }}</td>
                        {{-- Status --}}
                        <td class="px-4 py-2 text-center">
                            @if ($cr['status'] === 'ok')
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[9px] font-bold bg-emerald-100 text-emerald-700">✓ OK</span>
                            @elseif ($cr['status'] === 'late')
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[9px] font-bold bg-red-100 text-red-700">! LATE</span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[9px] font-semibold bg-gray-100 text-gray-500">— Pending</span>
                            @endif
                        </td>
                        {{-- Reason / Note --}}
                        <td class="px-4 py-2 text-gray-500 italic">
                            @if ($cr['delay_reason'])
                                <span class="text-red-600 not-italic font-medium">{{ $cr['delay_reason'] }}</span>
                                @if ($cr['note'])
                                    <span class="ml-1 text-gray-400">— {{ $cr['note'] }}</span>
                                @endif
                            @elseif ($cr['note'])
                                {{ $cr['note'] }}
                            @else
                                —
                            @endif
                        </td>
                        {{-- Action --}}
                        <td class="px-4 py-2 text-center">
                            <button wire:click="openOpModal({{ $cr['voyage_id'] }}, 'readiness')"
                                class="px-2.5 py-1 rounded border text-[10px] font-medium transition
                                    {{ $cr['status'] === 'pending'
                                        ? 'border-amber-200 bg-amber-50 text-amber-700 hover:bg-amber-100'
                                        : 'border-gray-200 bg-gray-50 text-gray-500 hover:bg-gray-100' }}">
                                {{ $cr['status'] === 'pending' ? 'Input' : 'Update' }}
                            </button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif


        {{-- ═══════════════════════════════════════════════════════════════ --}}
        {{-- OPERATIONAL MONITORING MATRIX (primary workspace)             --}}
        {{-- Scope: voyage dengan shipment aktif (cargo monitoring)        --}}
        {{-- ═══════════════════════════════════════════════════════════════ --}}
        @include('filament.pages.partials.tam-matrix-view')


        {{-- ═══════════════════════════════════════════════════════════════ --}}
        {{-- OPERATIONAL CALENDAR (below matrix)                           --}}
        {{-- ═══════════════════════════════════════════════════════════════ --}}
        @if (count($calendar))
            @include('filament.pages.partials.tam-calendar')
        @endif


        {{-- ═══════════════════════════════════════════════════════════════ --}}
        {{-- OPERATIONAL EVALUATION                                        --}}
        {{-- ═══════════════════════════════════════════════════════════════ --}}
        @if (!empty($evaluation))
            @include('filament.pages.partials.tam-evaluation')
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
                            <div class="font-semibold font-mono text-sm">
                                {{ $selectedMilestone->voyage->code ?? $selectedMilestone->voyage->voyage_no }}
                                @if ($selectedMilestone->voyage->code)
                                    <span class="text-gray-400 font-normal text-[10px]">({{ $selectedMilestone->voyage->voyage_no }})</span>
                                @endif
                            </div>
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


        {{-- ═══════════════════════════════════════════════════════════════ --}}
        {{-- OPERATIONAL ACTION MODAL (ATB/ATD/ATA/Closing/Delay/Readiness)--}}
        {{-- ═══════════════════════════════════════════════════════════════ --}}
        @if ($showActionModal)
            <div class="fixed inset-0 bg-black/40 flex items-center justify-center z-50"
                wire:click.self="closeOpModal">
                <div class="bg-white rounded-lg shadow-xl w-[360px] p-5">

                    {{-- Header --}}
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            @php
                                $modalTitles = [
                                    'atb'       => 'Input ATB',
                                    'atd'       => 'Input ATD',
                                    'ata'       => 'Input ATA',
                                    'closing'   => 'Closing Voyage',
                                    'delay'     => 'Catat Penyebab Delay',
                                    'readiness' => 'Readiness Check',
                                    'cargo'     => 'Input Cargo Actual',
                                ];
                                $modalTitle = $modalTitles[$actionModalType] ?? 'Aksi Operasional';

                                $drawerVoyage = $rows?->firstWhere('id', $actionVoyageId);
                            @endphp
                            <h3 class="text-sm font-semibold text-gray-800">{{ $modalTitle }}</h3>
                            @if ($drawerVoyage)
                                <p class="text-[10px] text-gray-500 font-mono mt-0.5">
                                    {{ $drawerVoyage->vessel?->name }} ·
                                    {{ $drawerVoyage->code ?? $drawerVoyage->voyage_no }}
                                </p>
                            @endif
                        </div>
                        <button wire:click="closeOpModal" class="text-gray-400 hover:text-gray-600">✕</button>
                    </div>

                    {{-- Body --}}
                    <div class="space-y-3">

                        {{-- ATB / ATD / ATA / Closing --}}
                        @if (in_array($actionModalType, ['atb', 'atd', 'ata', 'closing']))
                            @if ($drawerVoyage)
                                @php
                                    $planRef = match ($actionModalType) {
                                        'atb' => $drawerVoyage->etb ? 'ETB Plan: ' . $drawerVoyage->etb->format('d M Y H:i') : null,
                                        'atd' => $drawerVoyage->etd ? 'ETD Plan: ' . $drawerVoyage->etd->format('d M Y H:i') : null,
                                        'ata' => $drawerVoyage->eta ? 'ETA Plan: ' . $drawerVoyage->eta->format('d M Y H:i') : null,
                                        default => null,
                                    };
                                @endphp
                                @if ($planRef)
                                    <div class="bg-gray-50 rounded px-2.5 py-1.5 text-[10px] text-gray-500 font-medium">
                                        {{ $planRef }}
                                    </div>
                                @endif
                            @endif

                            <div>
                                <label class="block text-[10px] font-semibold text-gray-500 uppercase tracking-wider mb-1">
                                    Tanggal & Waktu Aktual
                                </label>
                                <input type="datetime-local" wire:model="actionForm.datetime"
                                    class="w-full rounded border-gray-200 text-xs py-1.5 px-2 focus:ring-0 focus:border-gray-400">
                                @error('actionForm.datetime')
                                    <p class="mt-1 text-[10px] text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-[10px] font-semibold text-gray-500 uppercase tracking-wider mb-1">
                                    Catatan Operasional
                                </label>
                                <textarea wire:model="actionForm.note" rows="2"
                                    class="w-full rounded border-gray-200 text-xs py-1.5 px-2 focus:ring-0 focus:border-gray-400 resize-none"
                                    placeholder="Opsional..."></textarea>
                                @error('actionForm.note')
                                    <p class="mt-1 text-[10px] text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        @endif

                        {{-- Delay --}}
                        @if ($actionModalType === 'delay')
                            <div>
                                <label class="block text-[10px] font-semibold text-gray-500 uppercase tracking-wider mb-1">
                                    Penyebab Delay
                                </label>
                                <select wire:model="actionForm.delay_reason"
                                    class="w-full rounded border-gray-200 text-xs py-1.5 px-2 focus:ring-0 focus:border-gray-400">
                                    <option value="">-- Pilih Penyebab --</option>
                                    @foreach (\App\Enums\VoyageDelayReason::cases() as $reason)
                                        <option value="{{ $reason->value }}">{{ $reason->label() }}</option>
                                    @endforeach
                                </select>
                                @error('actionForm.delay_reason')
                                    <p class="mt-1 text-[10px] text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="block text-[10px] font-semibold text-gray-500 uppercase tracking-wider mb-1">
                                    Keterangan
                                </label>
                                <textarea wire:model="actionForm.delay_note" rows="2"
                                    class="w-full rounded border-gray-200 text-xs py-1.5 px-2 focus:ring-0 focus:border-gray-400 resize-none"
                                    placeholder="Detail penyebab delay..."></textarea>
                                @error('actionForm.delay_note')
                                    <p class="mt-1 text-[10px] text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        @endif

                        {{-- Readiness --}}
                        @if ($actionModalType === 'readiness')
                            <div>
                                <label class="block text-[10px] font-semibold text-gray-500 uppercase tracking-wider mb-2">
                                    Status Kesiapan
                                </label>
                                <div class="flex gap-3">
                                    <label class="flex items-center gap-1.5 cursor-pointer">
                                        <input type="radio" wire:model.live="actionForm.readiness"
                                            value="{{ \App\Enums\VesselCheckLogStatus::OK->value }}"
                                            class="text-green-600 focus:ring-green-500">
                                        <span class="text-xs font-medium text-green-700">OK</span>
                                    </label>
                                    <label class="flex items-center gap-1.5 cursor-pointer">
                                        <input type="radio" wire:model.live="actionForm.readiness"
                                            value="{{ \App\Enums\VesselCheckLogStatus::LATE->value }}"
                                            class="text-red-500 focus:ring-red-400">
                                        <span class="text-xs font-medium text-red-700">Late</span>
                                    </label>
                                </div>
                                @error('actionForm.readiness')
                                    <p class="mt-1 text-[10px] text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            @if ($actionForm['readiness'] === \App\Enums\VesselCheckLogStatus::LATE->value)
                            <div>
                                <label class="block text-[10px] font-semibold text-gray-500 uppercase tracking-wider mb-1">
                                    Alasan Keterlambatan
                                </label>
                                <select wire:model="actionForm.readiness_delay_reason"
                                    class="w-full rounded border-gray-200 text-xs py-1.5 px-2 focus:ring-0 focus:border-gray-400">
                                    <option value="">-- Pilih Alasan --</option>
                                    @foreach (\App\Enums\VesselCheckDelayReason::cases() as $reason)
                                        <option value="{{ $reason->value }}">{{ $reason->label() }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @endif
                            <div>
                                <label class="block text-[10px] font-semibold text-gray-500 uppercase tracking-wider mb-1">
                                    Catatan
                                </label>
                                <textarea wire:model="actionForm.readiness_note" rows="2"
                                    class="w-full rounded border-gray-200 text-xs py-1.5 px-2 focus:ring-0 focus:border-gray-400 resize-none"
                                    placeholder="Opsional..."></textarea>
                            </div>
                        @endif

                        {{-- Cargo Actual --}}
                        @if ($actionModalType === 'cargo')
                            @php $cv = $rows?->firstWhere('id', $actionVoyageId); @endphp
                            @if ($cv?->cargo_plan !== null)
                                <div class="bg-gray-50 rounded px-2.5 py-1.5 text-[10px] text-gray-500 font-medium">
                                    Rencana: {{ $cv->cargo_plan }} unit
                                </div>
                            @endif
                            <div>
                                <label class="block text-[10px] font-semibold text-gray-500 uppercase tracking-wider mb-1">
                                    Jumlah Aktual (unit)
                                </label>
                                <input type="number" min="0" wire:model="actionForm.cargo"
                                    class="w-full rounded border-gray-200 text-xs py-1.5 px-2 focus:ring-0 focus:border-gray-400"
                                    placeholder="0">
                                @error('actionForm.cargo')
                                    <p class="mt-1 text-[10px] text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="block text-[10px] font-semibold text-gray-500 uppercase tracking-wider mb-1">
                                    Catatan
                                </label>
                                <textarea wire:model="actionForm.cargo_note" rows="2"
                                    class="w-full rounded border-gray-200 text-xs py-1.5 px-2 focus:ring-0 focus:border-gray-400 resize-none"
                                    placeholder="Opsional..."></textarea>
                                @error('actionForm.cargo_note')
                                    <p class="mt-1 text-[10px] text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        @endif

                    </div>

                    {{-- Footer --}}
                    <div class="mt-5 flex justify-end gap-2">
                        <button wire:click="closeOpModal"
                            wire:loading.attr="disabled"
                            class="px-3 py-1.5 border border-gray-200 rounded text-xs text-gray-600 hover:bg-gray-50 disabled:opacity-50">
                            Batal
                        </button>
                        <button wire:click="saveOpModal"
                            wire:loading.attr="disabled"
                            wire:target="saveOpModal"
                            class="px-3 py-1.5 bg-gray-900 text-white rounded text-xs hover:bg-gray-800 disabled:opacity-60 flex items-center gap-1.5">
                            <span wire:loading wire:target="saveOpModal"
                                class="inline-block w-3 h-3 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                            Simpan
                        </button>
                    </div>

                </div>
            </div>
        @endif


        {{-- ═══════════════════════════════════════════════════════════════ --}}
        {{-- VOYAGE DETAIL DRAWER                                          --}}
        {{-- ═══════════════════════════════════════════════════════════════ --}}
        @if ($showDrawer && $drawerVoyageId)
            @php
                $dv = $rows?->firstWhere('id', $drawerVoyageId);
            @endphp
            @if ($dv)
                <div class="fixed inset-0 z-40" wire:click.self="closeDrawer">
                    {{-- Backdrop --}}
                    <div class="absolute inset-0 bg-black/20"></div>

                    {{-- Drawer panel --}}
                    <div class="absolute right-0 top-0 h-full w-[380px] bg-white shadow-2xl flex flex-col overflow-hidden">

                        {{-- Drawer header --}}
                        <div class="flex items-start justify-between px-5 py-4 border-b border-gray-100">
                            <div>
                                <h2 class="text-sm font-bold text-gray-900">{{ $dv->vessel?->name }}</h2>
                                @if ($dv->code)
                                    <p class="text-[11px] text-gray-700 font-mono font-semibold mt-0.5">{{ $dv->code }}</p>
                                    <p class="text-[9px] text-gray-400 font-mono">({{ $dv->voyage_no }})</p>
                                @else
                                    <p class="text-[10px] text-gray-500 font-mono mt-0.5">{{ $dv->voyage_no }}</p>
                                @endif
                                <p class="text-[10px] text-gray-400 mt-0.5">
                                    {{ \App\Supports\BusinessRouteResolver::forVoyage($dv) }}
                                </p>
                            </div>
                            <button wire:click="closeDrawer" class="text-gray-400 hover:text-gray-600 mt-0.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>

                        {{-- Drawer body (scrollable) --}}
                        <div class="flex-1 overflow-y-auto px-5 py-4 space-y-5">

                            {{-- Status badge --}}
                            @php
                                $dvStatus = $dv->operational_status_enum;
                                $dvBadge = match ($dvStatus) {
                                    \App\Enums\VoyageOperationalStatus::DELAYED   => ['Delay', 'bg-red-50 text-red-700 border-red-200'],
                                    \App\Enums\VoyageOperationalStatus::SAILING   => ['Sailing', 'bg-blue-50 text-blue-700 border-blue-200'],
                                    \App\Enums\VoyageOperationalStatus::COMPLETED => ['Selesai', 'bg-green-50 text-green-700 border-green-200'],
                                    default                                       => ['Terjadwal', 'bg-gray-50 text-gray-600 border-gray-200'],
                                };
                            @endphp
                            <div>
                                <span class="inline-flex items-center px-2 py-0.5 rounded border text-[10px] font-semibold {{ $dvBadge[1] }}">
                                    {{ $dvBadge[0] }}
                                </span>
                                @if ($dv->departure_delay_days > 0)
                                    <span class="ml-1.5 text-[10px] text-red-600 font-medium">
                                        Delay {{ $dv->departure_delay_days }} hari
                                    </span>
                                @endif
                            </div>

                            {{-- Schedule grid --}}
                            <div>
                                <p class="text-[9px] font-semibold text-gray-400 uppercase tracking-wider mb-2">Jadwal</p>
                                <div class="grid grid-cols-2 gap-x-4 gap-y-2 text-xs">
                                    <div>
                                        <p class="text-[9px] text-gray-400">ETD</p>
                                        <p class="font-medium text-gray-700">{{ $dv->etd?->format('d M Y H:i') ?? '—' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-[9px] text-gray-400">ATD</p>
                                        <p class="font-medium {{ $dv->atd_at ? 'text-gray-900' : 'text-gray-300' }}">
                                            {{ $dv->atd_at?->format('d M Y H:i') ?? '—' }}
                                        </p>
                                    </div>
                                    <div>
                                        <p class="text-[9px] text-gray-400">ETA</p>
                                        <p class="font-medium text-gray-700">{{ $dv->eta?->format('d M Y H:i') ?? '—' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-[9px] text-gray-400">ATA</p>
                                        <p class="font-medium {{ $dv->ata_at ? 'text-gray-900' : 'text-gray-300' }}">
                                            {{ $dv->ata_at?->format('d M Y H:i') ?? '—' }}
                                        </p>
                                    </div>
                                    <div>
                                        <p class="text-[9px] text-gray-400">ETB</p>
                                        <p class="font-medium text-gray-700">{{ $dv->etb?->format('d M Y H:i') ?? '—' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-[9px] text-gray-400">ATB</p>
                                        <p class="font-medium {{ $dv->atb_at ? 'text-gray-900' : 'text-gray-300' }}">
                                            {{ $dv->atb_at?->format('d M Y H:i') ?? '—' }}
                                        </p>
                                    </div>
                                    <div>
                                        <p class="text-[9px] text-gray-400">Closing</p>
                                        <p class="font-medium {{ $dv->closing_at ? 'text-gray-900' : 'text-gray-300' }}">
                                            {{ $dv->closing_at?->format('d M Y H:i') ?? '—' }}
                                        </p>
                                    </div>
                                    <div>
                                        <p class="text-[9px] text-gray-400">OTD / OTA</p>
                                        <p class="font-medium text-gray-700">
                                            {{ $dv->otd_status?->value === 'ontime' ? '✓' : ($dv->otd_status ? 'NG' : '—') }}
                                            /
                                            {{ $dv->ota_status?->value === 'ontime' ? '✓' : ($dv->ota_status ? 'NG' : '—') }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            {{-- Delay reason --}}
                            @if ($dv->manual_delay_reason)
                                <div>
                                    <p class="text-[9px] font-semibold text-gray-400 uppercase tracking-wider mb-1">Penyebab Delay</p>
                                    <p class="text-xs text-red-700 font-medium">{{ $dv->manual_delay_reason->label() }}</p>
                                </div>
                            @endif

                            {{-- Final note --}}
                            @if ($dv->final_note)
                                <div>
                                    <p class="text-[9px] font-semibold text-gray-400 uppercase tracking-wider mb-1">Catatan Operasional</p>
                                    <p class="text-xs text-gray-600 whitespace-pre-line leading-relaxed">{{ $dv->final_note }}</p>
                                </div>
                            @endif

                            {{-- Milestones timeline --}}
                            @if ($dv->milestones->count())
                                <div>
                                    <p class="text-[9px] font-semibold text-gray-400 uppercase tracking-wider mb-2">Milestones</p>
                                    <div class="space-y-1.5">
                                        @foreach ($dv->milestones->sortBy('milestone_date') as $ms)
                                            @php
                                                $msStatus = match (true) {
                                                    (bool) $ms->actual_date => 'done',
                                                    (bool) $ms->is_overdue  => 'late',
                                                    default                 => 'pending',
                                                };
                                                $msColor = match ($msStatus) {
                                                    'done'    => 'bg-green-500',
                                                    'late'    => 'bg-red-500',
                                                    default   => 'bg-gray-300',
                                                };
                                            @endphp
                                            <div class="flex items-start gap-2">
                                                <div class="mt-1.5 w-1.5 h-1.5 rounded-full flex-shrink-0 {{ $msColor }}"></div>
                                                <div class="flex-1 min-w-0">
                                                    <p class="text-[10px] font-medium text-gray-700">{{ strtoupper($ms->code) }}</p>
                                                    <p class="text-[9px] text-gray-400">
                                                        Plan: {{ $ms->milestone_date?->format('d M') ?? '—' }}
                                                        @if ($ms->actual_date)
                                                            · Aktual: {{ $ms->actual_date->format('d M') }}
                                                        @endif
                                                    </p>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                        </div>

                        {{-- Drawer footer: quick actions --}}
                        <div class="px-5 py-3 border-t border-gray-100 bg-gray-50/50">
                            <div class="flex gap-1.5 flex-wrap">
                                <button wire:click="openOpModal({{ $dv->id }}, 'atd')"
                                    class="px-2.5 py-1 rounded border border-gray-200 bg-white text-[10px] text-gray-600 hover:border-blue-300 hover:text-blue-700 transition">
                                    ATD
                                </button>
                                <button wire:click="openOpModal({{ $dv->id }}, 'ata')"
                                    class="px-2.5 py-1 rounded border border-gray-200 bg-white text-[10px] text-gray-600 hover:border-blue-300 hover:text-blue-700 transition">
                                    ATA
                                </button>
                                <button wire:click="openOpModal({{ $dv->id }}, 'atb')"
                                    class="px-2.5 py-1 rounded border border-gray-200 bg-white text-[10px] text-gray-600 hover:border-gray-400 transition">
                                    ATB
                                </button>
                                <button wire:click="openOpModal({{ $dv->id }}, 'closing')"
                                    class="px-2.5 py-1 rounded border border-gray-200 bg-white text-[10px] text-gray-600 hover:border-gray-400 transition">
                                    Closing
                                </button>
                                <button wire:click="openOpModal({{ $dv->id }}, 'delay')"
                                    class="px-2.5 py-1 rounded border border-red-200 bg-red-50/40 text-[10px] text-red-600 hover:bg-red-50 transition">
                                    Delay
                                </button>
                                <button wire:click="openOpModal({{ $dv->id }}, 'readiness')"
                                    class="px-2.5 py-1 rounded border border-orange-200 bg-orange-50/40 text-[10px] text-orange-600 hover:bg-orange-50 transition">
                                    Readiness
                                </button>
                            </div>
                        </div>

                    </div>
                </div>
            @endif
        @endif

    </div>
</x-filament-panels::page>
