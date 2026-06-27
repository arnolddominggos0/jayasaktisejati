@if ($timeline && !empty($timeline->stages))
    <ol class="relative space-y-3">
        @foreach ($timeline->stages as $stage)
            <li class="flex items-start gap-3">
                {{-- Icon node --}}
                <span class="mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-full {{ $stage->state === 'completed' ? 'bg-green-100 text-green-600' : ($stage->state === 'current' ? 'bg-blue-100 text-blue-600' : ($stage->state === 'skeleton' ? 'bg-gray-100 text-gray-300' : 'bg-gray-50 text-gray-300')) }}">
                    <x-dynamic-component :component="$stage->icon" class="size-4" />
                </span>
                {{-- Stage info --}}
                <div class="flex flex-1 flex-col">
                    <span class="text-sm font-medium {{ $stage->state === 'completed' || $stage->state === 'current' ? 'text-gray-900' : 'text-gray-400' }}">
                        {{ $stage->label }}
                    </span>
                    @if ($stage->tracked_at)
                        <span class="text-xs text-gray-400">{{ $stage->tracked_at->format('d M Y H:i') }}</span>
                    @elseif ($stage->state === 'skeleton')
                        <span class="text-xs text-gray-300">Menunggu...</span>
                    @endif
                    @if ($stage->note)
                        <span class="text-xs text-gray-500">{{ $stage->note }}</span>
                    @endif
                </div>
            </li>
        @endforeach
    </ol>
    <p class="mt-3 text-xs text-gray-400">{{ $timeline->completed_count }} / {{ $timeline->total_count }} tahap selesai</p>
@else
    <p class="text-sm text-gray-400">Timeline belum tersedia.</p>
@endif