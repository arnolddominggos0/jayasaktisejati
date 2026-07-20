{{-- Supporting section, not a workspace card — no bg/border/shadow box.
     Same tier as Calendar (Divider over Border, Section over Card). --}}
<div>

    <div class="pb-2 border-b border-gray-100 flex items-center justify-between">
        <h3 class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider">
            Evaluasi Operasional — {{ \Illuminate\Support\Carbon::createFromFormat('Y-m', $period)->translatedFormat('F Y') }}
        </h3>
    </div>

    <div class="mt-3 grid grid-cols-1 md:grid-cols-3 gap-4">

        {{-- Delay Summary — number-first hierarchy (angka → label → satuan),
             flat, no card/border/shadow. All four cells share the same
             treatment: "Terlambat Terlama" is a historical statistic, not a
             live severity alert, so it gets no reserve-hue treatment — equal
             visual weight across all four. --}}
        <div class="space-y-2">
            <p class="text-[9px] font-bold text-gray-400 uppercase tracking-wider">Ringkasan Keterlambatan</p>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p class="text-2xl font-bold text-gray-900 tabular-nums leading-none">{{ $evaluation['total_delay'] }}</p>
                    <p class="text-[10px] text-gray-500 mt-1">Voyage Terlambat</p>
                    <p class="text-[9px] text-gray-400">voyage</p>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900 tabular-nums leading-none">{{ $evaluation['total_days'] }}</p>
                    <p class="text-[10px] text-gray-500 mt-1">Total Hari Terlambat</p>
                    <p class="text-[9px] text-gray-400">hari</p>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900 tabular-nums leading-none">{{ $evaluation['avg_days'] }}</p>
                    <p class="text-[10px] text-gray-500 mt-1">Rata-rata Terlambat</p>
                    <p class="text-[9px] text-gray-400">hari/voyage</p>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900 tabular-nums leading-none">{{ $evaluation['max_days'] }}</p>
                    <p class="text-[10px] text-gray-500 mt-1">Terlambat Terlama</p>
                    <p class="text-[9px] text-gray-400">hari</p>
                </div>
            </div>
        </div>

        {{-- ── Root Cause Summary ─────────────────────────────────────── --}}
        <div class="space-y-2">
            <p class="text-[9px] font-bold text-gray-400 uppercase tracking-wider">Penyebab Keterlambatan</p>
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
            <p class="text-[9px] font-bold text-gray-400 uppercase tracking-wider">Voyage Paling Terlambat</p>
            @if ($evaluation['top5']->isEmpty())
                <p class="text-[10px] text-gray-400 italic">Tidak ada voyage terlambat.</p>
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
                                {{ $tv->departure_delay_days }} Hari
                            </span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

    </div>

</div>
