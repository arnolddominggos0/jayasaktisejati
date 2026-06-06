<div class="bg-white border border-gray-200/40 rounded-lg overflow-hidden">

    <div class="px-4 py-2.5 border-b border-gray-100/60 flex items-center justify-between">
        <h2 class="text-[11px] font-semibold text-gray-700 uppercase tracking-wider">
            Evaluasi Operasional — {{ \Illuminate\Support\Carbon::createFromFormat('Y-m', $period)->translatedFormat('F Y') }}
        </h2>
    </div>

    <div class="p-4 grid grid-cols-1 md:grid-cols-3 gap-4">

        {{-- ── Delay Summary ──────────────────────────────────────────── --}}
        <div class="space-y-2">
            <p class="text-[9px] font-bold text-gray-400 uppercase tracking-wider">Delay Summary</p>
            <div class="grid grid-cols-2 gap-2">
                <div class="bg-gray-50/60 rounded p-2.5">
                    <p class="text-[9px] text-gray-400">Total Delay</p>
                    <p class="text-lg font-bold text-gray-800">{{ $evaluation['total_delay'] }}</p>
                    <p class="text-[9px] text-gray-400">voyage</p>
                </div>
                <div class="bg-gray-50/60 rounded p-2.5">
                    <p class="text-[9px] text-gray-400">Total Hari</p>
                    <p class="text-lg font-bold text-gray-800">{{ $evaluation['total_days'] }}</p>
                    <p class="text-[9px] text-gray-400">hari</p>
                </div>
                <div class="bg-gray-50/60 rounded p-2.5">
                    <p class="text-[9px] text-gray-400">Rata-rata</p>
                    <p class="text-lg font-bold text-gray-800">{{ $evaluation['avg_days'] }}</p>
                    <p class="text-[9px] text-gray-400">hari/voyage</p>
                </div>
                <div class="bg-red-50/40 rounded p-2.5">
                    <p class="text-[9px] text-red-400">Max Delay</p>
                    <p class="text-lg font-bold text-red-700">{{ $evaluation['max_days'] }}</p>
                    <p class="text-[9px] text-red-400">hari</p>
                </div>
            </div>
        </div>

        {{-- ── Root Cause Summary ─────────────────────────────────────── --}}
        <div class="space-y-2">
            <p class="text-[9px] font-bold text-gray-400 uppercase tracking-wider">Root Cause</p>
            @if ($evaluation['reasons']->isEmpty())
                <p class="text-[10px] text-gray-400 italic">Belum ada penyebab dicatat.</p>
            @else
                <div class="space-y-1.5">
                    @foreach ($evaluation['reasons'] as $reasonKey => $data)
                        @php
                            try {
                                $reasonLabel = \App\Enums\VoyageDelayReason::from($reasonKey)->label();
                            } catch (\ValueError) {
                                $reasonLabel = $reasonKey;
                            }
                        @endphp
                        <div>
                            <div class="flex items-center justify-between text-[10px] mb-0.5">
                                <span class="text-gray-600 font-medium">{{ $reasonLabel }}</span>
                                <span class="text-gray-500">{{ $data['count'] }} ({{ $data['percent'] }}%)</span>
                            </div>
                            <div class="h-1 bg-gray-100 rounded-full overflow-hidden">
                                <div class="h-full bg-red-400/60 rounded-full" style="width: {{ $data['percent'] }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- ── Top 5 Delay Voyage ─────────────────────────────────────── --}}
        <div class="space-y-2">
            <p class="text-[9px] font-bold text-gray-400 uppercase tracking-wider">Top Delay Voyage</p>
            @if ($evaluation['top5']->isEmpty())
                <p class="text-[10px] text-gray-400 italic">Tidak ada voyage delay.</p>
            @else
                <div class="space-y-1">
                    @foreach ($evaluation['top5'] as $i => $tv)
                        <div class="flex items-center gap-2 text-[10px]">
                            <span class="w-4 text-right text-gray-400 font-mono text-[9px]">{{ $i + 1 }}.</span>
                            <div class="flex-1 min-w-0">
                                <p class="font-medium text-gray-700 truncate">{{ $tv->vessel?->name }}</p>
                                <p class="text-gray-400 font-mono text-[9px]">{{ $tv->voyage_no }}</p>
                            </div>
                            <span class="text-red-600 font-bold whitespace-nowrap">
                                {{ $tv->departure_delay_days }}d
                            </span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

    </div>

</div>
