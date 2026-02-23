<div class="space-y-6">

@if($rows->where('operational_status','delayed')->count())
<div class="bg-red-50 border border-red-200 rounded-2xl p-6">
    <div class="font-semibold text-red-700 mb-4 text-sm uppercase">
        🚨 Critical - Terlambat
    </div>

    <div class="grid gap-4">
        @foreach($rows->where('operational_status','delayed') as $v)
        <div class="bg-white rounded-xl p-4 shadow-sm border border-red-100 flex justify-between items-center">

            <div>
                <div class="font-semibold">
                    {{ $v->vessel?->name }} — {{ $v->voyage_no }}
                </div>
                <div class="text-sm text-gray-500">
                    {{ $v->pol?->code }} → {{ $v->pod?->code }}
                </div>
                <div class="text-xs text-red-600 mt-1">
                    Overdue {{ $v->overdue_days }} hari
                </div>
            </div>

            <div class="text-right text-sm">
                <div>ETD: {{ optional($v->etd)->format('d M H:i') }}</div>
                <div>ETA: {{ optional($v->eta)->format('d M H:i') }}</div>
                <div class="text-xs text-gray-500 mt-1">
                    {{ $v->delay_reason?->label() }}
                </div>
            </div>

        </div>
        @endforeach
    </div>
</div>
@endif


@if($rows->where('operational_status','completed')->count())
<div class="bg-green-50 border border-green-200 rounded-2xl p-6">
    <div class="font-semibold text-green-700 mb-4 text-sm uppercase">
        ✅ Selesai
    </div>

    <div class="grid gap-4">
        @foreach($rows->where('operational_status','completed') as $v)
        <div class="bg-white rounded-xl p-4 shadow-sm border flex justify-between items-center">

            <div>
                <div class="font-semibold">
                    {{ $v->vessel?->name }} — {{ $v->voyage_no }}
                </div>
                <div class="text-sm text-gray-500">
                    {{ $v->pol?->code }} → {{ $v->pod?->code }}
                </div>
            </div>

            <div class="text-right text-sm">
                <div>ETD: {{ optional($v->etd)->format('d M H:i') }}</div>
                <div>ETA: {{ optional($v->eta)->format('d M H:i') }}</div>

                @if($v->sailingSla)
                    <span class="px-2 py-1 text-xs rounded
                        {{ $v->sailingSla->status === 'late'
                            ? 'bg-red-100 text-red-600'
                            : 'bg-green-100 text-green-600' }}">
                        {{ strtoupper($v->sailingSla->status) }}
                    </span>
                @endif
            </div>

        </div>
        @endforeach
    </div>
</div>
@endif

</div>