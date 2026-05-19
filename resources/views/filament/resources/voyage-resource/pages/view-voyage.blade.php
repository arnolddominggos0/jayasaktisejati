@php
    use App\Supports\OperationalUi;

    $v = $this->record;

    if (!$v) {
        echo '<div class="text-sm text-gray-500">Voyage tidak ditemukan.</div>';
        return;
    }

    $state = $v->operationalState;
    $statusBadge = OperationalUi::operationalStatusLight($state->status);
    $headerBorder = OperationalUi::severityBorder($state->severity);
@endphp

<x-filament-panels::page>
    <div class="max-w-6xl mx-auto space-y-5">

        <div class="flex items-start justify-between gap-4">
            <div>
                <div class="text-xs text-gray-400 uppercase tracking-wider font-semibold">
                    Registry Voyage
                </div>

                <h1 class="mt-1 text-3xl font-bold text-gray-900">
                    Lembar Eksekusi Operasional
                </h1>

                <div class="mt-1 text-sm text-gray-500">
                    Detail operasional voyage — untuk monitoring harian gunakan Monitoring Kapal TAM
                </div>
            </div>

            <a
                href="{{ url('/admin/monitoring-kapal-tam') }}"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-gray-200 bg-white text-sm font-medium text-gray-600 hover:bg-gray-50 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M15 19l-7-7 7-7" />
                </svg>

                Kembali ke Monitoring
            </a>
        </div>

        <div class="bg-white border border-gray-200 rounded-2xl {{ $headerBorder }} p-4 shadow-sm">
            <div class="flex items-start justify-between gap-5">

                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-3 flex-wrap">

                        <div class="text-[18px] font-bold text-gray-900">
                            {{ $v->vessel?->name }}
                        </div>

                        <div class="text-[11px] font-mono text-gray-500">
                            {{ $v->voyage_no }}
                        </div>

                        <div class="text-[11px] text-gray-400 uppercase">
                            {{ $v->pol?->code }}
                            →
                            {{ $v->pod?->code }}
                        </div>

                        <div class="text-[11px] text-gray-400 uppercase">
                            {{ $v->shippingLine?->code }}
                        </div>
                    </div>

                    <div class="mt-3 flex items-center gap-5 flex-wrap text-[12px]">
                        <div>
                            <span class="text-gray-400">ETD</span>
                            <span class="font-semibold text-gray-800">
                                {{ optional($v->etd)->format('d M Y H:i') }}
                            </span>
                        </div>

                        <div>
                            <span class="text-gray-400">ETA</span>
                            <span class="font-semibold text-gray-800">
                                {{ optional($v->eta)->format('d M Y H:i') }}
                            </span>
                        </div>

                        @if ($v->atd_at)
                        <div>
                            <span class="text-gray-400">ATD</span>
                            <span class="font-semibold text-emerald-700">
                                {{ optional($v->atd_at)->format('d M Y H:i') }}
                            </span>
                        </div>
                        @endif
                    </div>
                </div>

                <div class="shrink-0">
                    <x-operational.badge :label="strtoupper($state->status->label())" :color="$statusBadge['class']" size="sm" />
                </div>
            </div>
        </div>

        <div class="flex items-center gap-2 flex-wrap">
            {!! OperationalUi::kpiBadge($state->otb, 'OTB') !!}
            {!! OperationalUi::kpiBadge($state->otd, 'OTD') !!}
            {!! OperationalUi::kpiBadge($state->ota, 'OTA') !!}
        </div>

        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-5">
            <div class="flex items-center justify-between mb-4">
                <div class="text-[11px] uppercase tracking-wider font-bold text-gray-500">
                    Timeline Operasional
                </div>

                <div class="text-[11px] text-gray-400">
                    {{ collect($v->checkpoints)->count() + collect($v->vesselChecks)->count() + collect($v->milestones)->count() + ($v->delayLogs?->count() ?? 0) }} kejadian
                </div>
            </div>

            <x-voyage-operational-timeline :voyage="$v" />
        </div>

        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-5">
            <div class="flex items-center justify-between mb-4">
                <div class="text-[11px] uppercase tracking-wider font-bold text-gray-500">
                    Kesiapan
                </div>

                <div class="text-[11px] text-gray-400">
                    {{ collect($v->vesselChecks)->count() }} pemeriksaan
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                @forelse ($v->vesselChecks as $vc)
                @php
                $vcDisplay = OperationalUi::vesselCheckStatusLabel($vc);
                @endphp

                <div class="rounded-xl border border-gray-100 px-3 py-2">
                    <div class="flex items-center justify-between gap-3">
                        <div class="flex items-center gap-2">
                            <div class="text-[11px] font-mono text-gray-400">
                                VC
                            </div>

                            <div class="font-semibold text-sm text-gray-800">
                                {{ $vc->day_code }}
                            </div>
                        </div>

                        <div class="{{ $vcDisplay['class'] }} font-bold text-sm">
                            {{ $vcDisplay['label'] }}
                        </div>
                    </div>

                    @if ($vc->note)
                    <div class="mt-1 text-[12px] text-gray-500">
                        {{ $vc->note }}
                    </div>
                    @endif
                </div>
                @empty
                <div class="col-span-2 text-sm text-gray-400 italic">
                    Belum ada data kesiapan vessel.
                </div>
                @endforelse
            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-5">
            <div class="flex items-center justify-between mb-4">
                <div class="text-[11px] uppercase tracking-wider font-bold text-gray-500">
                    Milestone
                </div>

                <div class="text-[11px] text-gray-400">
                    {{ $state->milestoneTotalCount }} milestone
                </div>
            </div>

            @if ($state->milestoneTotalCount > 0)
            <div class="grid grid-cols-4 gap-3">
                @foreach ($v->milestones as $m)
                @php
                $chip = OperationalUi::milestoneChip($m);
                @endphp

                <div class="rounded-xl border px-3 py-3 {{ $chip['class'] }}">
                    <div class="flex items-center justify-between">
                        <div class="font-bold text-sm uppercase">
                            {{ $m->code }}
                        </div>

                        <div class="font-black">
                            {{ $chip['icon'] }}
                        </div>
                    </div>

                    <div class="mt-2 text-[11px]">
                        {{ optional($m->milestone_date)->format('d M Y') }}
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <div class="py-3 text-sm text-gray-400 italic">
                Belum ada milestone operasional.
            </div>
            @endif
        </div>

    </div>
</x-filament-panels::page>
