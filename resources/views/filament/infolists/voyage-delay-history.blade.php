@php
    $logs = collect($getState() ?? []);
@endphp

@if ($logs->isEmpty())
    <div class="text-[12px] text-gray-400 italic py-2">Tidak ada riwayat perubahan jadwal.</div>
@else
    <div class="space-y-0">
        @foreach ($logs as $log)
            <div class="flex items-start gap-2.5 py-2 px-2 border-l-2 border-l-red-300 bg-red-50/20">

                <span class="mt-0.5 w-5 h-5 flex-shrink-0 rounded bg-red-100 text-red-600 flex items-center justify-center text-[9px] font-bold">
                    !
                </span>

                <div class="flex-1 min-w-0">
                    {{-- ETD change --}}
                    @if ($log->old_etd && $log->new_etd)
                        <div class="text-[11px] font-medium text-gray-800">
                            ETD
                            <span class="text-red-500">{{ $log->old_etd->format('d M H:i') }}</span>
                            →
                            <span class="text-gray-900">{{ $log->new_etd->format('d M H:i') }}</span>
                        </div>
                    @endif

                    {{-- ETA change --}}
                    @if ($log->old_eta && $log->new_eta)
                        <div class="text-[10px] text-gray-500 mt-0.5">
                            ETA
                            {{ $log->old_eta->format('d M H:i') }}
                            →
                            {{ $log->new_eta->format('d M H:i') }}
                        </div>
                    @endif

                    {{-- Reason --}}
                    @if ($log->reason)
                        <div class="mt-1 text-[11px] text-orange-700 font-medium">
                            {{ $log->reason }}
                        </div>
                    @endif

                    {{-- Changed by --}}
                    @if ($log->changed_by)
                        <div class="mt-0.5 text-[10px] text-gray-400">
                            oleh {{ $log->changed_by }}
                        </div>
                    @endif
                </div>

                {{-- Timestamp --}}
                <div class="flex-shrink-0 text-[9px] text-gray-400 tabular-nums whitespace-nowrap text-right">
                    {{ $log->created_at?->format('d M Y') }}<br>
                    {{ $log->created_at?->format('H:i') }}
                </div>
            </div>
        @endforeach
    </div>
@endif
