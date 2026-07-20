{{--
    Operational Timeline

    Source of truth: UnitTimeline ViewModel, built by TimelineBuilder from
    Shipment::tracks (ShipmentTrack records with tracked_at timestamps).

    SEMANTIC NOTE — two "current" definitions coexist intentionally:
      · Stage badge (Status Operasional, above) = StageResolver → latestTrack.status
        → "tahap terakhir yang dicatat"
      · Marker ● di sini = TimelineBuilder → lastCompletedIndex + 1
        → "tahap berikutnya yang sedang berlangsung"
    Keduanya menjawab pertanyaan berbeda. Perbedaan ini BUKAN bug.
--}}
@if ($timeline && !empty($timeline->stages))
    <ol class="jss-timeline">
        @foreach ($timeline->stages as $stage)
            @php
                $isDone    = $stage->state === 'completed';
                $isCurrent = $stage->state === 'current';
                $nodeClass = $isDone
                    ? 'is-done zone-' . $stage->color_zone
                    : ($isCurrent ? 'is-current' : 'is-pending');
            @endphp
            <li class="jss-timeline-item">
                {{-- Node: ✓ completed · ● current · ○ future --}}
                <span class="jss-tl-node {{ $nodeClass }}" aria-hidden="true">
                    @if ($isDone)
                        <x-heroicon-s-check class="w-3.5 h-3.5" />
                    @elseif ($isCurrent)
                        <x-heroicon-s-arrow-right class="w-3 h-3" />
                    @else
                        <span class="jss-tl-dot"></span>
                    @endif
                </span>

                {{-- Stage content --}}
                <div class="jss-tl-content">
                    <span class="jss-tl-label {{ $isCurrent ? 'jss-tl-label--current' : ($isDone ? '' : 'jss-tl-label--future') }}">
                        {{ $stage->label }}
                    </span>

                    @if ($isDone && $stage->tracked_at)
                        <span class="jss-tl-ts tabular-nums">
                            {{ $stage->tracked_at->format('d M Y') }}
                            <span class="jss-tl-time">{{ $stage->tracked_at->format('H:i') }}</span>
                        </span>
                    @elseif ($isCurrent)
                        <span class="jss-tl-active">Sedang berlangsung</span>
                    @endif

                    @if ($stage->note)
                        <span class="jss-tl-note">{{ $stage->note }}</span>
                    @endif

                    @if ($stage->location)
                        <span class="jss-tl-loc">
                            <x-heroicon-o-map-pin class="w-2.5 h-2.5 flex-shrink-0" />
                            {{ $stage->location }}
                        </span>
                    @endif
                </div>
            </li>
        @endforeach
    </ol>
    <p class="jss-tl-footer tabular-nums">{{ $timeline->completed_count }} / {{ $timeline->total_count }} tahap selesai</p>
@else
    <p class="mon-caption">Timeline belum tersedia.</p>
@endif
