{{--
    Operational Brief / "Status Armada" — voyage-indexed cards.

    TaskClassifier (getBrief()) is invoked exactly once. This partial only
    renders — voyage grouping/dominance ranking happens in
    MonitoringKapalTam::getVoyageCards(), never reclassified in Blade.

    Each card shows one voyage's dominant Task, reusing the existing
    <x-operational.task-item> component, with an optional
    secondary-responsibility disclosure line beneath it — no Task is ever
    dropped, no second card is ever created for the same voyage.
--}}
@php
    $brief = $this->getBrief();

    // Healthy state is quiet, not celebrated: green is not the healthy signal.
    // Only the two reserve hues remain: red (critical) + amber (busy). Healthy
    // speaks by isolation + type size, never by color.
    $healthStyle = match ($brief['health']['state']) {
        'critical' => ['dot' => 'bg-red-500', 'text' => 'text-red-700'],
        'busy'     => ['dot' => 'bg-amber-400', 'text' => 'text-amber-700'],
        default    => ['dot' => 'bg-gray-300', 'text' => 'text-gray-700'],
    };

    $fs = $brief['fleetStatus'];
    $cardsByZone = collect($brief['voyageCards'])->groupBy('zone');
@endphp

<div class="bg-white border border-gray-200/60 rounded-lg overflow-hidden">

    {{-- STATUS ARMADA (Fleet Status) — condition sentence first,
         plain-language counts second, never a percentage, never a KPI card.
         Strongest visual anchor on the page via type scale/weight (16px bold
         vs. everything else's 13px-and-under), not a new colored panel/icon. --}}
    <div class="px-4 py-4 border-b border-gray-100">
        <div class="flex items-baseline gap-2">
            <span class="w-2.5 h-2.5 rounded-full {{ $healthStyle['dot'] }} self-center"></span>
            <span class="text-base font-bold uppercase tracking-wide {{ $healthStyle['text'] }}">
                {{ $brief['health']['label'] }}
            </span>
            <span class="text-[12px] text-gray-500">— {{ $brief['health']['reason'] }}</span>
        </div>
        <p class="text-[11px] text-gray-400 mt-1.5">
            Hari ini terdapat {{ $fs['total_active'] }} voyage aktif
            @if ($fs['needing_attention']) · {{ $fs['needing_attention'] }} memerlukan perhatian @endif
            @if ($fs['critical']) · {{ $fs['critical'] }} kritis @endif
            @if ($fs['departures_today']) · {{ $fs['departures_today'] }} keberangkatan hari ini @endif
            @if ($fs['arrivals_today']) · {{ $fs['arrivals_today'] }} kedatangan hari ini @endif
            .
        </p>
    </div>

    <div class="p-4 space-y-4">

        {{-- Voyages Needing Attention (Action zone) --}}
        @if ($cardsByZone->has('action'))
            <div>
                <div class="text-[10px] font-bold uppercase tracking-wider text-red-600 mb-1.5">
                    Perlu Tindakan Sekarang
                </div>
                <div class="space-y-1">
                    @foreach ($cardsByZone['action'] as $card)
                        <x-operational.task-item :item="$card['dominant']" tone="action" />
                        @if ($card['secondary_count'] > 0)
                            <p class="text-[10px] text-gray-400 pl-2.5">
                                + {{ $card['secondary_count'] }} tanggung jawab operasional lainnya pada voyage ini
                            </p>
                        @endif
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Menunggu Pihak Luar (Awaiting zone) --}}
        @if ($cardsByZone->has('awaiting'))
            <div>
                <div class="text-[10px] font-bold uppercase tracking-wider text-gray-400 mb-1.5">
                    Menunggu Pihak Luar
                </div>
                <div class="space-y-1">
                    @foreach ($cardsByZone['awaiting'] as $card)
                        <x-operational.task-item :item="$card['dominant']" tone="awaiting" />
                        @if ($card['secondary_count'] > 0)
                            <p class="text-[10px] text-gray-400 pl-2.5">
                                + {{ $card['secondary_count'] }} tanggung jawab operasional lainnya pada voyage ini
                            </p>
                        @endif
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Checkpoint Hari Ini --}}
        @if ($cardsByZone->has('checkpoint'))
            <div>
                <div class="text-[10px] font-bold uppercase tracking-wider text-amber-600 mb-1.5">
                    Checkpoint Hari Ini
                </div>
                <div class="space-y-1">
                    @foreach ($cardsByZone['checkpoint'] as $card)
                        <x-operational.task-item :item="$card['dominant']" tone="checkpoint" />
                        @if ($card['secondary_count'] > 0)
                            <p class="text-[10px] text-gray-400 pl-2.5">
                                + {{ $card['secondary_count'] }} tanggung jawab operasional lainnya pada voyage ini
                            </p>
                        @endif
                    @endforeach
                </div>
            </div>
        @endif

        {{-- On track — confirmation only (P1) --}}
        @if ($cardsByZone->isEmpty())
            <div class="text-xs text-gray-500">
                Tidak ada voyage yang memerlukan perhatian saat ini.
                @if ($brief['onTrack'])
                    {{ $brief['onTrack'] }} voyage berjalan normal.
                @endif
            </div>
        @elseif ($brief['onTrack'])
            <div class="text-[11px] text-gray-400 pt-1 border-t border-gray-100">
                {{ $brief['onTrack'] }} voyage lainnya berjalan normal, tidak memerlukan tindakan.
            </div>
        @endif

    </div>
</div>
