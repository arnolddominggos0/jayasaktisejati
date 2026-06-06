@php
    use App\Enums\VoyageOperationalStatus;

    $dateFmt = fn($dt) => $dt ? $dt->format('d M') : '—';

    // Priority sorting: DELAYED → ETA overdue → ETA risk → SAILING → COMPLETED → SCHEDULED
    $sorted = $rows->sortByDesc(function ($v) {
        return match (true) {
            $v->operational_status_enum === VoyageOperationalStatus::DELAYED => 100,
            $v->eta_overdue => 90,
            $v->sailing_risk => 80,
            $v->operational_status_enum === VoyageOperationalStatus::SAILING => 70,
            $v->checkpoints->contains(fn($cp) => !$cp->is_completed && $cp->scheduled_at?->isPast()) => 60,
            $v->vesselChecks->contains(fn($vc) => $vc->status?->value === 'potential_delay') => 50,
            $v->operational_status_enum === VoyageOperationalStatus::COMPLETED => 30,
            $v->operational_status_enum === VoyageOperationalStatus::SCHEDULED => 20,
            default => 10,
        };
    })->values();
@endphp

@if ($sorted->isEmpty())
    <div class="bg-white border rounded-lg p-6 text-center text-xs text-gray-500">
        Tidak ada pelayaran pada periode ini.
    </div>
@else
    <div class="overflow-x-auto bg-white border border-gray-200/40 rounded-lg shadow-sm">
        <table class="min-w-full text-[11px]">
            <thead class="bg-gray-50/50 text-gray-500 font-medium text-[10px] tracking-wide border-b border-gray-200/30">
                <tr>
                    {{-- Vessel identity --}}
                    <th class="px-2 py-1.5 text-left sticky left-0 bg-gray-50/70 z-10 min-w-[100px] border-r border-gray-200/20">Kapal</th>

                    {{-- Planning dates --}}
                    <th class="px-3 py-1.5 text-center">ETD</th>
                    <th class="px-3 py-1.5 text-center">ETA</th>

                    {{-- Actual dates --}}
                    <th class="px-3 py-1.5 text-center">ATD</th>
                    <th class="px-3 py-1.5 text-center">ATA</th>
                    <th class="px-3 py-1.5 text-center min-w-[80px]">Muatan</th>

                    {{-- Issue: moved closer to ATA --}}
                    <th class="px-3 py-1.5 text-center">Issue</th>

                    {{-- Readiness --}}
                    <th class="px-2 py-1.5 text-center">D-1</th>
                    <th class="px-2 py-1.5 text-center">H-1</th>

                    {{-- Milestones --}}
                    <th class="px-2 py-1.5 text-center">D+2</th>
                    <th class="px-2 py-1.5 text-center">D+4</th>
                    <th class="px-2 py-1.5 text-center">D+6</th>

                    {{-- KPI --}}
                    <th class="px-2 py-1.5 text-center">OTD</th>
                    <th class="px-2 py-1.5 text-center">OTA</th>

                    {{-- Actions --}}
                    <th class="px-2 py-1.5 text-center w-12">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($sorted as $index => $v)
                    @php
                        $cpMap = collect($v->checkpoints ?? [])->keyBy(fn($cp) => strtolower($cp->code));
                        $d1 = $cpMap->get('eta_d1');

                        $h1 = collect($v->vesselChecks ?? [])
                            ->sortByDesc('check_date')
                            ->first(fn($vc) => $vc->day_code && str_starts_with(strtolower($vc->day_code), 'h'));

                        $mMap = collect($v->milestones ?? [])->keyBy(fn($m) => strtolower($m->code));

                        $criticalIssues = [];
                        $secondaryIssues = [];

                        if ($v->overdue_days) $criticalIssues[] = 'Delayed ' . $v->overdue_days . 'd';
                        if ($v->eta_overdue) $criticalIssues[] = 'ETA Overdue';
                        if ($v->sailing_risk) $secondaryIssues[] = 'ETA Risk';
                        if ($v->milestones->where('is_overdue', true)->count()) $secondaryIssues[] = 'Overdue';
                        if ($d1 && !$d1->is_completed && $d1->scheduled_at?->isPast()) $secondaryIssues[] = 'D-1 Late';
                        if ($h1 && $h1->status?->value === 'potential_delay') $secondaryIssues[] = 'H-1 Risk';

                        $hasIssues = count($criticalIssues) > 0 || count($secondaryIssues) > 0;
                        $isAnomaly = $v->operational_status_enum === VoyageOperationalStatus::DELAYED || $v->eta_overdue || $v->sailing_risk || $hasIssues;

                        $rowBorder = match (true) {
                            $v->operational_status_enum === VoyageOperationalStatus::DELAYED => 'border-l-[3px] border-l-red-500',
                            $v->eta_overdue => 'border-l-[3px] border-l-red-500',
                            $v->sailing_risk => 'border-l-[3px] border-l-orange-400',
                            $hasIssues => 'border-l-[3px] border-l-amber-400',
                            default => 'border-l-[3px] border-l-transparent',
                        };

                        $rowBg = match (true) {
                            $v->operational_status_enum === VoyageOperationalStatus::DELAYED => 'bg-red-50/30',
                            $v->eta_overdue => 'bg-red-50/20',
                            $v->sailing_risk => 'bg-orange-50/15',
                            $hasIssues => 'bg-amber-50/10',
                            default => '',
                        };

                        $zebra = $index % 2 === 1 ? 'bg-gray-50/20' : '';

                        $hasMilestones = $v->operational_status_enum === VoyageOperationalStatus::SAILING && $v->milestones->count() > 0;
                        $firstMilestone = $hasMilestones ? $v->milestones->first() : null;

                        $statusBadge = match ($v->operational_status_enum) {
                            VoyageOperationalStatus::SAILING => ['label' => 'Sailing', 'class' => 'text-blue-600 border-blue-200/50 bg-blue-50/30'],
                            VoyageOperationalStatus::COMPLETED => ['label' => 'Done', 'class' => 'text-green-600 border-green-200/50 bg-green-50/30'],
                            default => ['label' => 'Sched', 'class' => 'text-gray-400 border-gray-200/50 bg-gray-50/20'],
                        };
                    @endphp

                    <tr class="transition border-b border-gray-100/20 {{ $rowBorder }} {{ $rowBg }} {{ $zebra }} hover:bg-gray-50/30">

                        {{-- Vessel identity (sticky left) --}}
                        <td class="px-2 py-1.5 sticky left-0 z-10 border-r border-gray-200/20 {{ $rowBg ?: ($zebra ?: 'bg-white') }} hover:bg-gray-50/30">
                            <button wire:click="openDrawer({{ $v->id }})" class="font-semibold text-gray-900 text-[11px] truncate leading-tight hover:text-blue-700 transition text-left w-full">{{ $v->vessel?->name }}</button>
                            <div class="text-[10px] text-gray-500 mt-0.5">{{ $v->voyage_no }}</div>
                            <div class="text-[9px] text-gray-400 mt-0.5">{{ $v->pol?->code ?? '-' }} → {{ $v->pod?->code ?? '-' }}</div>
                            @if ($v->operational_status_enum !== VoyageOperationalStatus::DELAYED)
                            <div class="mt-0.5">
                                <span class="inline-flex items-center px-1 py-0.5 rounded text-[9px] font-medium border {{ $statusBadge['class'] }}">
                                    {{ $statusBadge['label'] }}
                                </span>
                            </div>
                            @endif
                        </td>

                        {{-- Planning: wider spacing for scan --}}
                        <td class="px-3 py-1.5 text-center text-gray-600 whitespace-nowrap">{{ $dateFmt($v->etd) }}</td>
                        <td class="px-3 py-1.5 text-center text-gray-600 whitespace-nowrap">{{ $dateFmt($v->eta) }}</td>

                        {{-- Actual dates: bolder to indicate active --}}
                        <td class="px-3 py-1.5 text-center text-gray-800 whitespace-nowrap font-medium">{{ $dateFmt($v->atd_at) }}</td>
                        <td class="px-3 py-1.5 text-center text-gray-800 whitespace-nowrap font-medium">{{ $dateFmt($v->ata_at) }}</td>

                        {{-- Muatan: cargo plan vs actual --}}
                        <td class="px-3 py-1.5 text-center">
                            @if ($v->cargo_actual !== null)
                                <div class="text-[11px] font-semibold text-gray-800 leading-tight">{{ $v->cargo_actual }}</div>
                                @if ($v->cargo_plan !== null)
                                    @php $variance = $v->cargo_actual - $v->cargo_plan; @endphp
                                    <div class="text-[9px] {{ $variance < 0 ? 'text-red-500' : ($variance > 0 ? 'text-green-600' : 'text-gray-400') }}">
                                        {{ $variance >= 0 ? '+' : '' }}{{ $variance }}
                                    </div>
                                @endif
                            @else
                                @if ($v->cargo_plan !== null)
                                    <div class="text-[9px] text-gray-400 mb-0.5">{{ $v->cargo_plan }}</div>
                                @endif
                                <button wire:click="openOpModal({{ $v->id }}, 'cargo')"
                                    class="px-1.5 py-0.5 rounded border border-gray-200 text-[9px] text-gray-500 hover:border-blue-300 hover:text-blue-700 transition">
                                    Input
                                </button>
                            @endif
                        </td>

                        {{-- Issue: critical = pill, secondary = plain amber text --}}
                        <td class="px-3 py-1.5 text-center">
                            @if (count($criticalIssues) || count($secondaryIssues))
                                <div class="flex flex-col items-center gap-[2px]">
                                    @foreach ($criticalIssues as $issue)
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-medium bg-red-50/70 text-red-600 border border-red-200/40 whitespace-nowrap">
                                            {{ $issue }}
                                        </span>
                                    @endforeach
                                    @foreach ($secondaryIssues as $issue)
                                        <span class="text-[9px] text-amber-600/80 whitespace-nowrap leading-tight">
                                            ↳ {{ $issue }}
                                        </span>
                                    @endforeach
                                </div>
                            @else
                                <span class="text-gray-300 text-[10px]">—</span>
                            @endif
                        </td>

                        {{-- Readiness --}}
                        <td class="px-2 py-1.5 text-center">
                            @if ($d1)
                                @if ($d1->is_completed)
                                    <span class="text-green-600 text-[10px] font-medium">✓</span>
                                @elseif ($d1->is_late || ($d1->scheduled_at && $d1->scheduled_at->isPast()))
                                    <span class="text-red-600 text-[10px] font-semibold">!</span>
                                @else
                                    <span class="text-gray-400 text-[10px]">•</span>
                                @endif
                            @else
                                <span class="text-gray-300 text-[10px]">—</span>
                            @endif
                        </td>
                        <td class="px-2 py-1.5 text-center">
                            @if ($h1)
                                @if ($h1->status?->value === 'on_schedule')
                                    <span class="text-green-600 text-[10px] font-medium">✓</span>
                                @elseif ($h1->status?->value === 'potential_delay')
                                    <span class="text-red-600 text-[10px] font-semibold">!</span>
                                @else
                                    <span class="text-gray-400 text-[10px]">{{ strtoupper($h1->day_code ?? 'H-1') }}</span>
                                @endif
                            @else
                                <span class="text-gray-300 text-[10px]">—</span>
                            @endif
                        </td>

                        {{-- Milestones: symbol-based, 11px --}}
                        @php
                            $m2 = $mMap->get('d2');
                            $m4 = $mMap->get('d4');
                            $m6 = $mMap->get('d6');
                        @endphp
                        <td class="px-2 py-1.5 text-center">
                            @if ($m2)
                                @if ($m2->actual_date)
                                    <span class="text-green-600 text-[11px]">✓</span>
                                @elseif ($m2->is_overdue)
                                    <span class="text-red-500 text-[11px] font-bold">!</span>
                                @else
                                    <span class="text-gray-300 text-[11px]">•</span>
                                @endif
                            @else
                                <span class="text-gray-300 text-[10px]">—</span>
                            @endif
                        </td>
                        <td class="px-2 py-1.5 text-center">
                            @if ($m4)
                                @if ($m4->actual_date)
                                    <span class="text-green-600 text-[11px]">✓</span>
                                @elseif ($m4->is_overdue)
                                    <span class="text-red-500 text-[11px] font-bold">!</span>
                                @else
                                    <span class="text-gray-300 text-[11px]">•</span>
                                @endif
                            @else
                                <span class="text-gray-300 text-[10px]">—</span>
                            @endif
                        </td>
                        <td class="px-2 py-1.5 text-center">
                            @if ($m6)
                                @if ($m6->actual_date)
                                    <span class="text-green-600 text-[11px]">✓</span>
                                @elseif ($m6->is_overdue)
                                    <span class="text-red-500 text-[11px] font-bold">!</span>
                                @else
                                    <span class="text-gray-300 text-[11px]">•</span>
                                @endif
                            @else
                                <span class="text-gray-300 text-[10px]">—</span>
                            @endif
                        </td>

                        {{-- KPI: NG subtle red pill, OK muted text --}}
                        <td class="px-2 py-1.5 text-center">
                            @if ($v->otd_status)
                                @if ($v->otd_status->value === 'late')
                                    <span class="inline-flex items-center px-1 py-0.5 rounded text-[9px] font-medium bg-red-50/60 text-red-600 border border-red-200/40">NG</span>
                                @else
                                    <span class="text-gray-400 text-[10px]">✓</span>
                                @endif
                            @else
                                <span class="text-gray-300 text-[10px]">—</span>
                            @endif
                        </td>
                        <td class="px-2 py-1.5 text-center">
                            @if ($v->ota_status)
                                @if ($v->ota_status->value === 'late')
                                    <span class="inline-flex items-center px-1 py-0.5 rounded text-[9px] font-medium bg-red-50/60 text-red-600 border border-red-200/40">NG</span>
                                @else
                                    <span class="text-gray-400 text-[10px]">✓</span>
                                @endif
                            @else
                                <span class="text-gray-300 text-[10px]">—</span>
                            @endif
                        </td>

                        {{-- Actions: operational buttons --}}
                        <td class="px-2 py-1.5 text-center">
                            <div class="flex items-center justify-center gap-0.5 flex-wrap">

                                {{-- Detail drawer --}}
                                <button wire:click="openDrawer({{ $v->id }})"
                                    class="inline-flex items-center justify-center w-5 h-5 rounded text-gray-400 hover:text-blue-600 hover:bg-blue-50/60 transition"
                                    title="Detail Voyage">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                </button>

                                {{-- ATD --}}
                                <button wire:click="openOpModal({{ $v->id }}, 'atd')"
                                    class="inline-flex items-center justify-center w-5 h-5 rounded text-[8px] font-bold
                                        {{ $v->atd_at ? 'text-green-600 bg-green-50/40 border border-green-200/50' : 'text-gray-400 hover:text-blue-600 hover:bg-blue-50/60' }} transition"
                                    title="Input ATD">
                                    <span>D</span>
                                </button>

                                {{-- ATA --}}
                                <button wire:click="openOpModal({{ $v->id }}, 'ata')"
                                    class="inline-flex items-center justify-center w-5 h-5 rounded text-[8px] font-bold
                                        {{ $v->ata_at ? 'text-green-600 bg-green-50/40 border border-green-200/50' : 'text-gray-400 hover:text-blue-600 hover:bg-blue-50/60' }} transition"
                                    title="Input ATA">
                                    <span>A</span>
                                </button>

                                {{-- ATB --}}
                                <button wire:click="openOpModal({{ $v->id }}, 'atb')"
                                    class="inline-flex items-center justify-center w-5 h-5 rounded text-[8px] font-bold
                                        {{ $v->atb_at ? 'text-green-600 bg-green-50/40 border border-green-200/50' : 'text-gray-400 hover:text-gray-600 hover:bg-gray-100/60' }} transition"
                                    title="Input ATB">
                                    <span>B</span>
                                </button>

                                {{-- Closing --}}
                                <button wire:click="openOpModal({{ $v->id }}, 'closing')"
                                    class="inline-flex items-center justify-center w-5 h-5 rounded text-gray-400 hover:text-gray-700 hover:bg-gray-100/60 transition"
                                    title="Closing">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                </button>

                                {{-- Delay --}}
                                <button wire:click="openOpModal({{ $v->id }}, 'delay')"
                                    class="inline-flex items-center justify-center w-5 h-5 rounded text-gray-400 hover:text-red-600 hover:bg-red-50/60 transition"
                                    title="Catat Penyebab Delay">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                </button>

                                {{-- Readiness --}}
                                <button wire:click="openOpModal({{ $v->id }}, 'readiness')"
                                    class="inline-flex items-center justify-center w-5 h-5 rounded text-gray-400 hover:text-orange-600 hover:bg-orange-50/60 transition"
                                    title="Readiness Check">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </button>

                                @if ($hasMilestones && $firstMilestone)
                                    <button wire:click="showMilestone({{ $firstMilestone->id }})"
                                        class="inline-flex items-center justify-center w-5 h-5 rounded text-gray-400 hover:text-gray-600 hover:bg-gray-100/60 transition"
                                        title="Milestone">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                    </button>
                                @endif

                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
