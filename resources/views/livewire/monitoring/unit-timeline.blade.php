@if ($timeline && !empty($timeline->stages))
    <ol class="relative space-y-3">
        @foreach ($timeline->stages as $stage)
            <li class="flex items-start gap-3">
                {{-- Icon node (Task 2: +20% → 20px effective via 2rem box) --}}
                <span class="mon-timeline-node {{ $stage->state === 'completed' ? 'is-done' : ($stage->state === 'current' ? 'is-current' : 'is-pending') }}">
                    <x-dynamic-component :component="$stage->icon" class="w-4 h-4" />
                </span>
                {{-- Stage info --}}
                <div class="flex flex-1 flex-col">
                    <span class="mon-table font-medium {{ $stage->state === 'completed' || $stage->state === 'current' ? 'text-gray-900' : 'text-gray-400' }}">
                        {{ $stage->label }}
                    </span>
                    @if ($stage->tracked_at)
                        <span class="mon-caption tabular-nums">{{ $stage->tracked_at->format('d M Y H:i') }}</span>
                    @elseif ($stage->state === 'skeleton')
                        <span class="mon-caption" style="color: var(--mon-neutral-400);">Menunggu...</span>
                    @endif
                    @if ($stage->note)
                        <span class="mon-caption text-gray-500">{{ $stage->note }}</span>
                    @endif
                </div>
            </li>
        @endforeach
    </ol>
    <p class="mt-3 mon-caption tabular-nums">{{ $timeline->completed_count }} / {{ $timeline->total_count }} tahap selesai</p>
@else
    <p class="mon-caption">Timeline belum tersedia.</p>
@endif