<div class="space-y-6">
    @foreach($rows as $v)
        @php
            $totalCp = $v->checkpoints->count();
            $done = $v->checkpoints->whereNotNull('checked_at')->count();
            $pending = $v->checkpoints->whereNull('checked_at')->count();
            $overdue = $v->checkpoints->filter(fn($cp) =>
                !$cp->checked_at && $cp->scheduled_at?->isPast()
            )->count();

            $status = $v->operational_status_enum;
            $statusBadge = \App\Supports\OperationalUi::operationalStatusLight($status);
        @endphp

        @if($totalCp > 0)
        <div class="bg-white rounded-xl border p-4">
            <div class="flex justify-between items-center">
                <div>
                    <div class="flex items-center gap-2">
                        <div class="font-semibold">
                            {{ $v->vessel?->name }} — {{ $v->voyage_no }}
                        </div>
                        <x-operational.badge :label="$statusBadge['label']" :color="$statusBadge['class']" size="xs" />
                    </div>
                    <div class="text-xs text-gray-500 mt-1">
                        {{ $v->pol?->code }} → {{ $v->pod?->code }}
                    </div>
                </div>

                <div class="flex gap-3 text-xs">
                    <x-operational.badge label="Total {{ $totalCp }}" color="bg-gray-100 text-gray-700 border-gray-200" size="xs" />
                    @if($pending > 0)
                        <x-operational.badge label="Menunggu {{ $pending }}" color="bg-orange-100 text-orange-700 border-orange-200" size="xs" />
                    @endif
                    @if($overdue > 0)
                        <x-operational.badge label="Lewat {{ $overdue }}" color="bg-red-100 text-red-700 border-red-200" size="xs" />
                    @endif
                    @if($done > 0)
                        <x-operational.badge label="OK {{ $done }}" color="bg-emerald-100 text-emerald-700 border-emerald-200" size="xs" />
                    @endif
                </div>
            </div>
        </div>
        @endif
    @endforeach
</div>
