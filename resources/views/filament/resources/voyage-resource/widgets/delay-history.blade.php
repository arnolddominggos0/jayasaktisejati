<div class="space-y-3">
    @if($logs->isEmpty())
        <div class="text-xs text-gray-400 italic py-2">
            No delay records.
        </div>
    @else
        <div class="space-y-1">
            @foreach($logs as $log)
                <div class="flex items-center gap-2 py-1.5 border-b border-gray-100/40 last:border-0">
                    <div class="w-6 h-6 rounded-full bg-red-100 text-red-600 flex items-center justify-center text-[9px] font-bold flex-shrink-0">
                        !
                    </div>

                    <div class="flex-1 min-w-0">
                        <div class="text-[10px] text-gray-800 font-medium truncate">
                            {{ $log->reason ?: 'Schedule change' }}
                        </div>
                        <div class="text-[9px] text-gray-500 truncate">
                            ETD: {{ optional($log->old_etd)->format('d M H:i') }} → {{ optional($log->new_etd)->format('d M H:i') }}
                        </div>
                    </div>

                    <div class="text-[9px] text-gray-400 flex-shrink-0">
                        {{ $log->created_at?->format('d M H:i') }}
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
