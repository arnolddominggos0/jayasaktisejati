@php
    use App\Enums\VoyageOperationalStatus;

    $dateFmt = fn($dt) => $dt ? $dt->format('d M') : '·';
    $timeFmt = fn($dt) => $dt ? $dt->format('H:i') : null;

    $sorted = $rows->sortByDesc(fn($v) => $v->operationalState->priorityWeight())->values();
@endphp

@if ($sorted->isEmpty())
    <div class="bg-white border rounded-xl p-10 text-center text-sm text-gray-500">
        Tidak ada voyage pada periode ini.
    </div>
@else
    <div class="overflow-x-auto bg-white border border-gray-200 rounded-xl shadow-sm">
        <table class="w-full min-w-[1750px] text-[12px]">
            <thead
                class="sticky top-0 z-30 bg-white/95 backdrop-blur border-b border-gray-200 text-[11px] uppercase tracking-wider text-gray-500">
                <tr>
                    <th
                        class="sticky left-0 z-20 min-w-[300px] bg-white border-r border-gray-100 px-4 py-3 text-left">
                        Voyage
                    </th>

                    <th class="px-3 py-3 text-center">ETD</th>
                    <th class="px-3 py-3 text-center">ETA</th>
                    <th class="px-3 py-3 text-center">ETB</th>

                    <th class="px-3 py-3 text-center">ATD</th>
                    <th class="px-3 py-3 text-center">ATA</th>
                    <th class="px-3 py-3 text-center">ATB</th>
                    <th class="px-3 py-3 text-center">Closing</th>

                    <th class="px-4 py-3 text-left min-w-[220px]">
                        Tindakan Berikutnya
                    </th>

                    <th class="px-3 py-3 text-center min-w-[150px]">
                        Masalah
                    </th>

                    <th class="px-2 py-3 text-center">D-1</th>
                    <th class="px-2 py-3 text-center">H-1</th>

                    <th class="px-2 py-3 text-center">D+2</th>
                    <th class="px-2 py-3 text-center">D+4</th>
                    <th class="px-2 py-3 text-center">D+6</th>

                    <th class="px-2 py-3 text-center">OTD</th>
                    <th class="px-2 py-3 text-center">OTA</th>

                    <th class="px-3 py-3 text-center">Aksi</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-100">
                @foreach ($sorted as $v)
                    @php
                        $cpMap = collect($v->checkpoints ?? [])->keyBy(fn($cp) => strtolower($cp->code));

                        $d1 = $cpMap->get('eta_d1');

                        $h1 = collect($v->vesselChecks ?? [])
                            ->sortByDesc('check_date')
                            ->first(fn($vc) => str_starts_with(strtolower($vc->day_code ?? ''), 'h'));

                        $d1Vc = collect($v->vesselChecks ?? [])->first(
                            fn($vc) => strtolower($vc->day_code ?? '') === 'd-1',
                        );

                        $mMap = collect($v->milestones ?? [])->keyBy(fn($m) => strtolower($m->code));

                        $criticalIssues = [];
                        $warningIssues = [];
                        $infoIssues = [];

                        if ($v->overdue_days > 0) {
                            $criticalIssues[] = 'Delay ' . $v->overdue_days . ' hari';
                        }

                        if ($v->eta_overdue) {
                            $criticalIssues[] = 'ETA Lewat';
                        }

                        if ($v->sailing_risk) {
                            $warningIssues[] = 'Risiko ETA';
                        }

                        if ($h1 && $h1->status?->value === 'potential_delay') {
                            $warningIssues[] = 'Risiko H-1';
                        }

                        if ($v->milestones->where('is_overdue', true)->count()) {
                            $infoIssues[] = 'Milestone Lewat';
                        }

                        if ($v->manual_delay_reason) {
                            $infoIssues[] = $v->manual_delay_reason->label();
                        }

                        $hasIssues =
                            count($criticalIssues) ||
                            count($warningIssues) ||
                            count($infoIssues);

                        $severity = match (true) {
                            count($criticalIssues) > 0 => 'critical',
                            count($warningIssues) > 0 => 'warning',
                            count($infoIssues) > 0 => 'attention',
                            default => 'normal',
                        };

                        $rowClass = match ($severity) {
                            'critical' => 'border-l-4 border-l-red-500 bg-red-50/40',
                            'warning' => 'border-l-4 border-l-orange-400 bg-orange-50/20',
                            'attention' => 'border-l-4 border-l-amber-400 bg-amber-50/10',
                            default => 'border-l-4 border-l-transparent',
                        };

                        $statusBadge = match ($v->operational_status_enum) {
                            VoyageOperationalStatus::SAILING => [
                                'label' => 'Berlayar',
                                'class' => 'bg-blue-50 text-blue-700 border-blue-200',
                            ],
                            VoyageOperationalStatus::COMPLETED => [
                                'label' => 'Selesai',
                                'class' => 'bg-green-50 text-green-700 border-green-200',
                            ],
                            VoyageOperationalStatus::DELAYED => [
                                'label' => 'Delay',
                                'class' => 'bg-red-50 text-red-700 border-red-200',
                            ],
                            default => [
                                'label' => 'Terjadwal',
                                'class' => 'bg-gray-50 text-gray-600 border-gray-200',
                            ],
                        };

                        $nextAction = match (true) {
                            !$v->atd_at => 'Input ATD',
                            !$v->ata_at && $v->operational_status_enum === VoyageOperationalStatus::SAILING
                                => 'Monitor ATA',
                            !$h1 && $v->operational_status_enum === VoyageOperationalStatus::SAILING
                                => 'Lengkapi H-1',
                            $h1 && $h1->status?->value === 'potential_delay'
                                => 'Review Risiko H-1',
                            $v->eta_overdue => 'Buat Investigasi Delay',
                            default => 'Monitoring Normal',
                        };

                        $nextActionClass = match (true) {
                            str_contains($nextAction, 'Delay') => 'text-red-700 bg-red-50 border-red-200',
                            str_contains($nextAction, 'Risiko') => 'text-orange-700 bg-orange-50 border-orange-200',
                            str_contains($nextAction, 'Input') => 'text-blue-700 bg-blue-50 border-blue-200',
                            default => 'text-gray-600 bg-gray-50 border-gray-200',
                        };

                        $lastUpdate = $v->updated_at;

                        $lastUpdateClass = match (true) {
                            optional($lastUpdate)->lt(now()->subHours(12))
                                => 'text-red-500',
                            optional($lastUpdate)->lt(now()->subHours(4))
                                => 'text-amber-500',
                            default => 'text-gray-400',
                        };
                    @endphp

                    <tr class="{{ $rowClass }} hover:bg-slate-50 transition">
                        <td class="sticky left-0 z-10 px-4 py-3 border-r border-gray-100 bg-white">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="font-bold text-[14px] text-gray-900 truncate">
                                        {{ $v->vessel?->name }}
                                    </div>

                                    <div class="mt-0.5 text-[11px] text-gray-500 font-mono">
                                        {{ $v->voyage_no }}
                                    </div>

                                    <div class="mt-1 text-[11px] text-gray-400">
                                        {{ $v->pol?->code ?? '-' }}
                                        →
                                        {{ $v->pod?->code ?? '-' }}
                                    </div>

                                    <div class="mt-2 flex items-center gap-2 flex-wrap">
                                        <span
                                            class="inline-flex items-center px-2 py-0.5 rounded-md border text-[10px] font-semibold {{ $statusBadge['class'] }}">
                                            {{ $statusBadge['label'] }}
                                        </span>

                                        <span class="text-[10px] {{ $lastUpdateClass }}">
                                            Update {{ optional($lastUpdate)->format('H:i') }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="text-[9px] text-gray-400/50 mt-0.5">{{ $v->voyage_no }}</div>
                        </td>

                        <td class="px-3 py-3 text-center text-gray-400">
                            {{ $dateFmt($v->etd) }}
                        </td>

                        <td class="px-3 py-3 text-center text-gray-400">
                            {{ $dateFmt($v->eta) }}
                        </td>

                        <td class="px-3 py-3 text-center text-gray-400">
                            {{ $dateFmt($v->etb) }}
                        </td>

                        <td class="px-3 py-3 text-center">
                            @if ($v->atd_at)
                                <div class="font-semibold text-gray-900">
                                    {{ $dateFmt($v->atd_at) }}
                                </div>
                            @else
                                <button
                                    wire:click="openAtdModal({{ $v->id }})"
                                    class="px-2 py-1 rounded-md border border-gray-200 text-[10px] text-gray-500 hover:border-blue-300 hover:text-blue-700 transition">
                                    Input
                                </button>
                            @endif
                        </td>

                        <td class="px-3 py-3 text-center">
                            @if ($v->ata_at)
                                <div class="font-semibold text-gray-900">
                                    {{ $dateFmt($v->ata_at) }}
                                </div>
                            @else
                                <button
                                    wire:click="openAtaModal({{ $v->id }})"
                                    class="px-2 py-1 rounded-md border border-gray-200 text-[10px] text-gray-500 hover:border-blue-300 hover:text-blue-700 transition">
                                    Input
                                </button>
                            @endif
                        </td>

                        <td class="px-3 py-3 text-center">
                            @if ($v->atb_at)
                                <div class="font-semibold text-gray-900">
                                    {{ $dateFmt($v->atb_at) }}
                                </div>
                            @else
                                <button
                                    wire:click="openAtbModal({{ $v->id }})"
                                    class="px-2 py-1 rounded-md border border-gray-200 text-[10px] text-gray-500 hover:border-blue-300 hover:text-blue-700 transition">
                                    Input
                                </button>
                            @endif
                        </td>

                        <td class="px-3 py-3 text-center">
                            @if ($v->closing_at)
                                <div class="font-semibold text-gray-900">
                                    {{ $dateFmt($v->closing_at) }}
                                </div>
                            @else
                                <button
                                    wire:click="openClosingModal({{ $v->id }})"
                                    class="px-2 py-1 rounded-md border border-gray-200 text-[10px] text-gray-500 hover:border-blue-300 hover:text-blue-700 transition">
                                    Input
                                </button>
                            @endif
                        </td>

                        <td class="px-4 py-3">
                            <div
                                class="inline-flex items-center px-2.5 py-1 rounded-lg border text-[11px] font-medium {{ $nextActionClass }}">
                                {{ $nextAction }}
                            </div>
                        </td>

                        <td class="px-3 py-3">
                            @if ($hasIssues)
                                <div class="flex flex-col gap-1">
                                    @foreach ($criticalIssues as $issue)
                                        <div
                                            class="inline-flex items-center px-2 py-1 rounded-md bg-red-100 text-red-700 border border-red-200 text-[11px] font-bold">
                                            {{ $issue }}
                                        </div>
                                    @endforeach

                                    @foreach ($warningIssues as $issue)
                                        <div class="text-[11px] text-orange-700 font-medium">
                                            ↳ {{ $issue }}
                                        </div>
                                    @endforeach

                                    @foreach ($infoIssues as $issue)
                                        <div class="text-[11px] text-gray-500">
                                            ↳ {{ $issue }}
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <span class="text-gray-300">·</span>
                            @endif
                        </td>

                        <td class="px-2 py-3 text-center">
                            @if ($d1)
                                @if ($d1->is_completed)
                                    <div
                                        class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-green-100 text-green-700 font-bold">
                                        ✓
                                    </div>
                                @elseif ($d1->is_late)
                                    <div
                                        class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-red-100 text-red-700 font-bold">
                                        !
                                    </div>
                                @else
                                    <div
                                        class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-gray-100 text-gray-400">
                                        •
                                    </div>
                                @endif
                            @else
                                <span class="text-gray-300">·</span>
                            @endif
                        </td>

                        <td class="px-2 py-3 text-center">
                            @if ($h1)
                                @if ($h1->status?->value === 'on_schedule')
                                    <div
                                        class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-green-100 text-green-700 font-bold">
                                        ✓
                                    </div>
                                @elseif ($h1->status?->value === 'potential_delay')
                                    <div
                                        class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-red-100 text-red-700 font-bold">
                                        !
                                    </div>
                                @else
                                    <div
                                        class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-gray-100 text-gray-400">
                                        •
                                    </div>
                                @endif
                            @else
                                <span class="text-gray-300">·</span>
                            @endif
                        </td>

                        @foreach (['d2', 'd4', 'd6'] as $code)
                            @php $m = $mMap->get($code); @endphp

                            <td class="px-2 py-3 text-center">
                                @if ($m)
                                    <div
                                        class="inline-flex items-center justify-center w-7 h-7 rounded-full
                                        {{ $m->actual_date
                                            ? ($m->status === 'ontime'
                                                ? 'bg-green-100 text-green-700'
                                                : 'bg-red-100 text-red-700')
                                            : ($m->is_overdue
                                                ? 'bg-red-100 text-red-700'
                                                : 'bg-gray-100 text-gray-400') }}">
                                        @if ($m->actual_date)
                                            {{ $m->status === 'ontime' ? '✓' : '✗' }}
                                        @elseif ($m->is_overdue)
                                            !
                                        @else
                                            •
                                        @endif
                                    </div>
                                @else
                                    <span class="text-gray-300">·</span>
                                @endif
                            </td>
                        @endforeach

                        <td class="px-2 py-3 text-center">
                            @if ($v->otd_status?->value === 'late')
                                <span
                                    class="inline-flex items-center px-1.5 py-0.5 rounded bg-red-100 text-red-700 border border-red-200 text-[10px] font-bold">
                                    NG
                                </span>
                            @elseif ($v->otd_status)
                                <span class="text-green-600 font-bold">✓</span>
                            @else
                                <span class="text-gray-300">·</span>
                            @endif
                        </td>

                        <td class="px-2 py-3 text-center">
                            @if ($v->ota_status?->value === 'late')
                                <span
                                    class="inline-flex items-center px-1.5 py-0.5 rounded bg-red-100 text-red-700 border border-red-200 text-[10px] font-bold">
                                    NG
                                </span>
                            @elseif ($v->ota_status)
                                <span class="text-green-600 font-bold">✓</span>
                            @else
                                <span class="text-gray-300">·</span>
                            @endif
                        </td>

                        <td class="px-3 py-3 text-center">
                            <div class="flex items-center justify-center gap-1">
                                <a
                                    href="{{ \App\Filament\Resources\VoyageResource::getUrl('view', ['record' => $v]) }}"
                                    target="_blank"
                                    class="w-8 h-8 rounded-lg hover:bg-gray-100 flex items-center justify-center text-gray-400 hover:text-gray-700 transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </a>
                            </div>
                        </td>

                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif