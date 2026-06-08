@php
    use App\Supports\OperationalUi;

    $milestones = collect($getState() ?? []);
    $total      = $milestones->count();
    $completed  = $milestones->whereNotNull('actual_date')->count();
@endphp

@if ($milestones->isEmpty())
    <div class="text-[12px] text-gray-400 italic py-2">Belum ada data milestone.</div>
@else
    <div class="mb-2 flex items-center justify-between">
        <span class="text-[11px] font-semibold text-gray-600 uppercase tracking-wide">
            Progress Milestone
        </span>
        <span class="text-[11px] text-gray-400">{{ $completed }} / {{ $total }} selesai</span>
    </div>

    <div class="flex gap-1.5 overflow-x-auto pb-1">
        @foreach ($milestones as $m)
            @php $chip = OperationalUi::milestoneChip($m); @endphp

            <div class="flex-none w-[72px] rounded border {{ $chip['class'] }} px-1.5 py-2 text-center"
                 title="{{ $chip['title'] }}">
                <div class="text-[9px] uppercase font-semibold tracking-wide">
                    {{ strtoupper($m->code) }}
                </div>
                <div class="text-base font-bold mt-0.5 leading-none">{{ $chip['icon'] }}</div>

                @if ($m->actual_date)
                    <div class="text-[8px] mt-1 tabular-nums">
                        {{ $m->actual_date->format('d M') }}
                    </div>
                    @if ($m->status === 'late')
                        <div class="text-[8px] text-red-600">terlambat</div>
                    @endif
                @elseif ($m->milestone_date)
                    <div class="text-[8px] mt-1 tabular-nums text-gray-400">
                        {{ $m->milestone_date->format('d M') }}
                    </div>
                    @if ($m->is_overdue)
                        <div class="text-[8px] text-red-600">overdue</div>
                    @elseif ($m->is_due_today)
                        <div class="text-[8px] text-orange-600">hari ini</div>
                    @endif
                @endif
            </div>
        @endforeach
    </div>
@endif
