@php
    use App\Supports\OperationalUi;

    $sorted = $rows->sortByDesc(fn($v) => $v->operationalState->priorityWeight())->values();
@endphp

@if ($sorted->isEmpty())
    <div class="bg-white border border-gray-200 rounded-2xl p-10 text-center text-sm text-gray-500">
        Tidak ada voyage pada periode ini.
    </div>
@else
    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
        <table class="w-full">
            <tbody class="divide-y divide-gray-100">
                @foreach ($sorted as $v)
                    @php
                        $state = $v->operationalState;
                        $severity = $state->severity;

                        // Severity: thicker border + subtle row tint
                        $sevBorder = match ($severity) {
                            'critical' => 'border-l-[5px] border-l-red-500',
                            'warning'  => 'border-l-[5px] border-l-orange-400',
                            default    => 'border-l-[5px] border-l-transparent',
                        };
                        $sevTint = match ($severity) {
                            'critical' => 'bg-red-50/25',
                            'warning'  => 'bg-orange-50/15',
                            default    => '',
                        };

                        // Readiness
                        $cpMap = collect($v->checkpoints ?? [])->keyBy(fn($cp) => strtolower($cp->code));
                        $d1 = $cpMap->get('eta_d1');
                        $h1 = collect($v->vesselChecks ?? [])
                            ->sortByDesc('check_date')
                            ->first(fn($vc) => str_starts_with(strtolower($vc->day_code ?? ''), 'h'));

                        // Semantic readiness — only show completed (✓) or overdue (⚠/✕)
                        $readiness = [];
                        if ($d1) {
                            if ($d1->is_completed) {
                                $readiness[] = ['icon' => '✓', 'label' => 'D-1', 'color' => 'bg-emerald-50 text-emerald-700 border-emerald-200'];
                            } elseif ($d1->is_late || $d1->scheduled_at?->isPast()) {
                                $readiness[] = ['icon' => '⚠', 'label' => 'D-1', 'color' => 'bg-red-50 text-red-700 border-red-200'];
                            }
                            // pending/neutral = invisible — suppresses noise
                        }
                        if ($h1) {
                            $h1State = $h1->status?->value;
                            if ($h1State === 'on_schedule') {
                                $readiness[] = ['icon' => '✓', 'label' => 'H-1', 'color' => 'bg-emerald-50 text-emerald-700 border-emerald-200'];
                            } elseif ($h1State === 'potential_delay') {
                                $readiness[] = ['icon' => '✕', 'label' => 'H-1', 'color' => 'bg-red-50 text-red-700 border-red-200'];
                            }
                            // neutral = invisible
                        }

                        // Primary focal issue
                        $primaryIssue = $state->hasCriticalIssues()
                            ? ($state->criticalIssues[0] ?? null)
                            : ($state->hasWarningIssues()
                                ? ($state->warningIssues[0] ?? null)
                                : null);

                        // KPI
                        $otdOk = $state->kpiOk('otd');
                        $otaOk = $state->kpiOk('ota');

                        // CTA priority
                        $ctaType = $state->canInputAtd ? 'atd' : ($state->canInputAta ? 'ata' : ($state->canAcknowledge ? 'tindak' : null));
                    @endphp

                    <tr class="group hover:bg-slate-50/70 transition-colors {{ $sevBorder }} {{ $sevTint }}">

                        {{-- IDENTITY: vessel dominan, route secondary, voyage_no subtle --}}
                        <td class="pl-4 pr-3 py-2.5 align-top min-w-[190px]">
                            <div class="text-[14px] font-bold text-gray-900 leading-tight">{{ $v->vessel?->name }}</div>
                            <div class="text-[10px] text-gray-600 mt-0.5">
                                {{ $v->pol?->code ?? '-' }} → {{ $v->pod?->code ?? '-' }}
                                @if ($v->shippingLine?->name)
                                    <span class="text-gray-300">·</span> {{ $v->shippingLine->name }}
                                @endif
                            </div>
                            <div class="text-[9px] text-gray-400/50 mt-0.5">{{ $v->voyage_no }}</div>
                        </td>

                        {{-- STATUS TIMER --}}
                        <td class="px-2 py-2.5 align-middle text-center min-w-[40px]">
                            @if ($state->status->value === 'sailing' && $state->sailingDays)
                                <span class="inline-block text-[10px] font-bold text-blue-600 bg-blue-50 border border-blue-200 rounded px-1.5 py-0.5">D+{{ $state->sailingDays }}</span>
                            @elseif ($state->status->value === 'scheduled' && $state->daysUntilEtd !== null)
                                <span class="inline-block text-[10px] text-gray-500 bg-gray-50 border border-gray-200 rounded px-1.5 py-0.5">E-{{ $state->daysUntilEtd }}</span>
                            @elseif ($state->status->value === 'delayed' && $v->overdue_days)
                                <span class="inline-block text-[10px] font-bold text-red-600 bg-red-50 border border-red-200 rounded px-1.5 py-0.5">+{{ $v->overdue_days }}d</span>
                            @endif
                        </td>

                        {{-- READINESS: semantic indicators ⚠ D-1 / ✓ H-1 / ✕ CP --}}
                        <td class="px-2 py-2.5 align-middle min-w-[65px]">
                            <div class="flex items-center gap-1">
                                @if (count($readiness))
                                    @foreach ($readiness as $r)
                                        <span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded text-[10px] font-semibold border {{ $r['color'] }}">
                                            {{ $r['icon'] }} {{ $r['label'] }}
                                        </span>
                                    @endforeach
                                @else
                                    <span class="text-[10px] text-gray-300">—</span>
                                @endif
                            </div>
                        </td>

                        {{-- PRIMARY ISSUE — FOCAL POINT, dominant, severity-driven --}}
                        <td class="px-3 py-2.5 align-middle flex-1">
                            @if ($primaryIssue)
                                <span class="inline-block px-2.5 py-1 rounded text-[12px] font-extrabold
                                    {{ $state->hasCriticalIssues()
                                        ? 'bg-red-100 text-red-800 border-2 border-red-300'
                                        : 'bg-orange-100 text-orange-800 border-2 border-orange-300' }}">
                                    {{ $primaryIssue }}
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-emerald-50 text-emerald-600 border border-emerald-200">Aman</span>
                            @endif
                        </td>

                        {{-- KPI ULTRA-LIGHT --}}
                        <td class="px-2 py-2.5 align-middle min-w-[60px]">
                            <div class="flex items-center gap-1.5">
                                @if ($state->otd)
                                    <span class="inline-flex items-center gap-0.5 text-[10px] font-extrabold {{ $otdOk ? 'text-emerald-700' : 'text-red-700' }}">
                                        OTD{{ $otdOk ? ' ✓' : ' ✕' }}
                                    </span>
                                @endif
                                @if ($state->ota)
                                    <span class="inline-flex items-center gap-0.5 text-[10px] font-extrabold {{ $otaOk ? 'text-emerald-700' : 'text-red-700' }}">
                                        OTA{{ $otaOk ? ' ✓' : ' ✕' }}
                                    </span>
                                @endif
                                @if (!$state->otd && !$state->ota)
                                    <span class="text-[10px] text-gray-300">—</span>
                                @endif
                            </div>
                        </td>

                        {{-- CTA — rightmost, operational label --}}
                        <td class="pl-2 pr-4 py-2.5 align-middle text-right whitespace-nowrap min-w-[90px]">
                            @if ($ctaType === 'atd')
                                <button wire:click="openAtdModal({{ $v->id }})"
                                    class="inline-flex items-center px-3 py-1.5 rounded text-[11px] font-bold bg-blue-600 text-white hover:bg-blue-700 transition shadow-sm">
                                    Input ATD
                                </button>
                            @elseif ($ctaType === 'ata')
                                <button wire:click="openAtaModal({{ $v->id }})"
                                    class="inline-flex items-center px-3 py-1.5 rounded text-[11px] font-bold bg-blue-600 text-white hover:bg-blue-700 transition shadow-sm">
                                    Input ATA
                                </button>
                            @elseif ($ctaType === 'tindak')
                                <button wire:click="acknowledgeVoyage({{ $v->id }})"
                                    class="inline-flex items-center px-3 py-1.5 rounded text-[11px] font-bold
                                        {{ $severity === 'critical'
                                            ? 'bg-red-600 text-white hover:bg-red-700'
                                            : 'bg-orange-50 text-orange-700 border border-orange-300 hover:bg-orange-100' }} transition shadow-sm">
                                    Tindak
                                </button>
                            @endif

                            <a href="{{ \App\Filament\Resources\VoyageResource::getUrl('view', ['record' => $v]) }}"
                                target="_blank"
                                class="ml-2 inline-flex items-center justify-center w-7 h-7 rounded border border-gray-200 bg-white text-gray-400 hover:text-blue-600 hover:border-blue-200 hover:bg-blue-50 transition"
                                title="Buka detail voyage">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </a>
                        </td>

                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif