@php
    use App\Supports\OperationalUi;

    $dateFmt = fn($dt) => $dt ? $dt->format('d M') : '·';

    $sorted = $rows
        ->sortByDesc(fn($v) => $v->operationalState->priorityWeight())
        ->values();
@endphp

@if ($sorted->isEmpty())
    <div class="bg-white border border-gray-200 rounded-2xl p-10 text-center text-sm text-gray-500">
        Tidak ada voyage pada periode ini.
    </div>
@else
    <div class="overflow-x-auto bg-white border border-gray-200 rounded-2xl shadow-sm">
        <table class="w-full min-w-[1280px] text-xs">
            <thead class="sticky top-0 z-30 bg-white border-b border-gray-200">
                <tr class="text-[11px] uppercase tracking-wider text-gray-500 font-semibold">
                    <th class="sticky left-0 z-20 min-w-[290px] bg-white border-r border-gray-100 px-5 py-4 text-left">
                        Voyage
                    </th>
                    <th class="px-3 py-4 text-center">ETD</th>
                    <th class="px-3 py-4 text-center">ETA</th>
                    <th class="px-3 py-4 text-center">ATD</th>
                    <th class="px-3 py-4 text-center">ATA</th>
                    <th class="px-4 py-4 text-left min-w-[190px]">Tindakan</th>
                    <th class="px-4 py-4 text-left min-w-[180px]">Masalah</th>
                    <th class="px-4 py-4 text-center min-w-[120px]">Kesiapan</th>
                    <th class="px-4 py-4 text-center min-w-[140px]">Delivery</th>
                    <th class="px-3 py-4 text-center">OTD</th>
                    <th class="px-3 py-4 text-center">OTA</th>
                    <th class="px-3 py-4 text-center">Detail</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-100">
                @foreach ($sorted as $v)
                    @php
                        $state = $v->operationalState;
                        $cpMap = collect($v->checkpoints ?? [])->keyBy(fn($cp) => strtolower($cp->code));
                        $d1 = $cpMap->get('eta_d1');
                        $h1 = collect($v->vesselChecks ?? [])
                            ->sortByDesc('check_date')
                            ->first(fn($vc) => str_starts_with(strtolower($vc->day_code ?? ''), 'h'));
                        $mMap = collect($v->milestones ?? [])->keyBy(fn($m) => strtolower($m->code));

                        $severity = $state->severity;
                        $statusBadge = OperationalUi::operationalStatusLight($state->status);
                        $nextAction = $state->nextActionLabel;

                        $lastUpdate = $v->updated_at;
                        $d2 = $mMap->get('d2');
                        $d4 = $mMap->get('d4');
                        $d6 = $mMap->get('d6');
                    @endphp

                    <tr class="{{ OperationalUi::severityBorder($severity) }} hover:bg-slate-50/60 transition">
                        <td class="sticky left-0 z-10 px-5 py-4 border-r border-gray-100 bg-white shadow-[2px_0_4px_-2px_rgba(0,0,0,0.04)]">
                            <div class="min-w-0">
                                <div class="font-bold text-[15px] text-gray-900 truncate">
                                    {{ $v->vessel?->name }}
                                </div>
                                <div class="mt-1 text-[11px] text-gray-500 font-mono">
                                    {{ $v->voyage_no }}
                                </div>
                                <div class="mt-2 text-[11px] text-gray-400">
                                    {{ $v->pol?->code ?? '-' }} → {{ $v->pod?->code ?? '-' }}
                                </div>
                                <div class="mt-3 flex items-center gap-2 flex-wrap">
                                    <x-operational.badge :label="$statusBadge['label']" :color="$statusBadge['class']" size="xs" />
                                    <span class="text-[10px] text-gray-400">
                                        Update {{ optional($lastUpdate)->format('H:i') }}
                                    </span>
                                </div>
                            </div>
                        </td>

                        <td class="px-3 py-4 text-center text-gray-400 font-medium">
                            {{ $dateFmt($v->etd) }}
                        </td>
                        <td class="px-3 py-4 text-center text-gray-400 font-medium">
                            {{ $dateFmt($v->eta) }}
                        </td>
                        <td class="px-3 py-4 text-center">
                            @if ($v->atd_at)
                                <div class="font-semibold text-gray-900">{{ $dateFmt($v->atd_at) }}</div>
                            @else
                                <button wire:click="openAtdModal({{ $v->id }})"
                                    class="px-2.5 py-1 rounded-lg border border-gray-200 text-[10px] font-medium text-gray-500 hover:border-blue-300 hover:bg-blue-50 hover:text-blue-700 transition">
                                    Input
                                </button>
                            @endif
                        </td>
                        <td class="px-3 py-4 text-center">
                            @if ($v->ata_at)
                                <div class="font-semibold text-gray-900">{{ $dateFmt($v->ata_at) }}</div>
                            @else
                                <button wire:click="openAtaModal({{ $v->id }})"
                                    class="px-2.5 py-1 rounded-lg border border-gray-200 text-[10px] font-medium text-gray-500 hover:border-blue-300 hover:bg-blue-50 hover:text-blue-700 transition">
                                    Input
                                </button>
                            @endif
                        </td>

                        <td class="px-4 py-4">
                            <x-operational.badge :label="$nextAction" :color="OperationalUi::nextActionClasses($nextAction)" size="sm" />
                        </td>

                        <td class="px-4 py-4">
                            @if ($state->hasCriticalIssues())
                                <div class="flex flex-col gap-1.5">
                                    @foreach ($state->criticalIssues as $issue)
                                        <x-operational.badge :label="$issue" color="bg-red-100 text-red-700 border-red-200" size="xs" />
                                    @endforeach
                                    @foreach ($state->warningIssues as $issue)
                                        <div class="text-[11px] text-orange-700 font-medium">↳ {{ $issue }}</div>
                                    @endforeach
                                </div>
                            @else
                                <span class="text-gray-300">—</span>
                            @endif
                        </td>

                        <td class="px-4 py-4">
                            <div class="flex items-center justify-center gap-2">
                                @if ($d1)
                                    @php $st = OperationalUi::checkpointCell($d1); @endphp
                                    <x-operational.indicator :state="$st['state']" :label="$st['label']" />
                                @else
                                    <x-operational.indicator />
                                @endif
                                @if ($h1)
                                    @php $st = OperationalUi::vesselCheckCell($h1); @endphp
                                    <x-operational.indicator :state="$st['state']" :label="$st['label']" />
                                @else
                                    <x-operational.indicator />
                                @endif
                            </div>
                        </td>

                        <td class="px-4 py-4">
                            <div class="flex items-center justify-center gap-2">
                                @foreach ([$d2, $d4, $d6] as $m)
                                    @if ($m)
                                        <x-operational.indicator :state="OperationalUi::milestoneIndicatorState($m)" :label="OperationalUi::milestoneChip($m)['icon']" />
                                    @else
                                        <x-operational.indicator />
                                    @endif
                                @endforeach
                            </div>
                        </td>

                        <td class="px-3 py-4 text-center">
                            @if ($state->otd)
                                <x-operational.kpi-badge :status="$state->otd" label="OTD" />
                            @else
                                <span class="text-gray-300">—</span>
                            @endif
                        </td>

                        <td class="px-3 py-4 text-center">
                            @if ($state->ota)
                                <x-operational.kpi-badge :status="$state->ota" label="OTA" />
                            @else
                                <span class="text-gray-300">—</span>
                            @endif
                        </td>

                        <td class="px-3 py-4 text-center">
                            <a href="{{ \App\Filament\Resources\VoyageResource::getUrl('view', ['record' => $v]) }}"
                                target="_blank"
                                class="inline-flex items-center justify-center w-9 h-9 rounded-xl border border-gray-200 bg-white hover:bg-blue-50 hover:border-blue-200 text-gray-400 hover:text-blue-700 transition">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12h6m-6 4h6M7 4h10a2 2 0 012 2v12a2 2 0 01-2 2H7a2 2 0 01-2-2V6a2 2 0 012-2z" />
                                </svg>
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
