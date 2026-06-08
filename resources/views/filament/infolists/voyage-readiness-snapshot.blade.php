@php
    $checks = collect($getState() ?? [])
        ->sortBy(fn ($vc) => match (strtolower($vc->day_code ?? '')) {
            'h-2', 'd-2' => 1,
            'h-1', 'd-1' => 2,
            default      => 99,
        });
@endphp

@if ($checks->isEmpty())
    <div class="text-[12px] text-gray-400 italic py-2">Belum ada data kesiapan vessel.</div>
@else
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
        @foreach ($checks as $vc)
            @php
                $statusEnum = $vc->status instanceof \App\Enums\VesselCheckLogStatus
                    ? $vc->status
                    : \App\Enums\VesselCheckLogStatus::tryFrom($vc->status ?? '');

                $isOk        = $statusEnum?->isOk() ?? false;
                $statusLabel = $statusEnum?->label() ?? strtoupper($vc->status ?? '—');
                $statusColor = $isOk ? 'text-emerald-700 font-bold' : 'text-red-700 font-bold';
                $borderColor = $isOk ? 'border-emerald-100' : 'border-red-100';
                $bgColor     = $isOk ? 'bg-emerald-50/30' : 'bg-red-50/20';
            @endphp

            <div class="rounded-xl border {{ $borderColor }} {{ $bgColor }} px-3 py-2.5">
                <div class="flex items-center justify-between gap-3">
                    <span class="text-[11px] font-mono text-gray-500 uppercase">
                        {{ $vc->day_code ?? '—' }}
                    </span>
                    <span class="{{ $statusColor }} text-sm">{{ $statusLabel }}</span>
                </div>

                @if ($vc->delay_reason)
                    <div class="mt-1 text-[11px] text-orange-600">
                        Alasan: {{ $vc->delay_reason }}
                    </div>
                @endif

                @if ($vc->note)
                    <div class="mt-1 text-[11px] text-gray-500 italic">{{ $vc->note }}</div>
                @endif

                @if ($vc->check_date)
                    <div class="mt-1.5 text-[10px] text-gray-400 tabular-nums">
                        {{ $vc->check_date->format('d M Y') }}
                    </div>
                @endif
            </div>
        @endforeach
    </div>
@endif
