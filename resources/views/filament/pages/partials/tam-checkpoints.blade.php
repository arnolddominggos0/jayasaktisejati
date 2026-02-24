<div class="space-y-6">

    @foreach($rows as $v)

        @php
            $totalCp = $v->checkpoints->count();
            $done = $v->checkpoints->whereNotNull('checked_at')->count();
            $pending = $v->checkpoints->whereNull('checked_at')->count();
            $overdue = $v->checkpoints->filter(fn($cp) =>
                !$cp->checked_at && $cp->scheduled_at?->isPast()
            )->count();
        @endphp

        @if($totalCp > 0)
        <div class="bg-white rounded-xl border p-4">

            <div class="flex justify-between items-center">

                <div>
                    <div class="font-semibold">
                        {{ $v->vessel?->name }} — {{ $v->voyage_no }}
                    </div>
                    <div class="text-xs text-gray-500">
                        {{ $v->pol?->code }} → {{ $v->pod?->code }}
                    </div>
                </div>

                <div class="flex gap-3 text-xs">

                    <span class="px-2 py-1 bg-gray-100 rounded">
                        Total {{ $totalCp }}
                    </span>

                    @if($pending > 0)
                        <span class="px-2 py-1 bg-orange-100 text-orange-700 rounded">
                            Pending {{ $pending }}
                        </span>
                    @endif

                    @if($overdue > 0)
                        <span class="px-2 py-1 bg-red-100 text-red-700 rounded">
                            Overdue {{ $overdue }}
                        </span>
                    @endif

                    @if($done > 0)
                        <span class="px-2 py-1 bg-green-100 text-green-700 rounded">
                            OK {{ $done }}
                        </span>
                    @endif

                </div>

            </div>

        </div>
        @endif

    @endforeach

</div>