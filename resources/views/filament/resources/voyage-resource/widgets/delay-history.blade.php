<div class="space-y-0">
    @if ($logs->isEmpty())
        <div class="text-[11px] text-gray-400 italic py-2">Belum ada catatan delay.</div>
    @else
        @foreach ($logs as $log)
            <div class="flex items-center gap-2 py-1.5 px-2 border-l-2 border-l-red-300 bg-red-50/20">
                <span class="w-5 h-5 rounded bg-red-100 text-red-600 flex items-center justify-center text-[9px] font-bold flex-shrink-0">!</span>
                <div class="flex-1 min-w-0">
                    <div class="text-[11px] text-gray-800 font-medium truncate">{{ $log->reason ?: 'Schedule change' }}</div>
                    <div class="text-[10px] text-gray-500 tabular-nums">
                        ETD {{ optional($log->old_etd)->format('d M H:i') ?? '—' }} → {{ optional($log->new_etd)->format('d M H:i') ?? '—' }}
                    </div>
                </div>
                <div class="text-[9px] text-gray-400 flex-shrink-0 tabular-nums">
                    {{ $log->created_at?->format('d M H:i') }}
                </div>
            </div>
        @endforeach
    @endif
</div>
