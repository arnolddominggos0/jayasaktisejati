<div class="space-y-6">
    @foreach($rows as $v)
        @php
            $state = $v->operationalState;
            $statusBadge = \App\Supports\OperationalUi::operationalStatusLight($state->status);
        @endphp

        @if($state->milestoneTotalCount > 0)
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
                    <x-operational.badge label="Total {{ $state->milestoneTotalCount }}" color="bg-gray-100 text-gray-700 border-gray-200" size="xs" />
                    @if($state->milestoneTotalCount - $state->milestoneCompletedCount > 0)
                        <x-operational.badge label="Menunggu {{ $state->milestoneTotalCount - $state->milestoneCompletedCount }}" color="bg-orange-100 text-orange-700 border-orange-200" size="xs" />
                    @endif
                    @if($state->milestoneOverdueCount > 0)
                        <x-operational.badge label="Lewat {{ $state->milestoneOverdueCount }}" color="bg-red-100 text-red-700 border-red-200" size="xs" />
                    @endif
                    @if($state->milestoneCompletedCount > 0)
                        <x-operational.badge label="OK {{ $state->milestoneCompletedCount }}" color="bg-emerald-100 text-emerald-700 border-emerald-200" size="xs" />
                    @endif
                </div>
            </div>
        </div>
        @endif
    @endforeach
</div>