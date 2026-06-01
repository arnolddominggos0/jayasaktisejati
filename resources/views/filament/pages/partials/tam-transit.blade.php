<div class="space-y-6">

    @php
    use App\Enums\VoyageOperationalStatus;

    $sailingVoyages = $rows->filter(fn($v) =>
        $v->operational_status_enum === VoyageOperationalStatus::SAILING
    );
@endphp

    @forelse($sailingVoyages as $v)

        <div class="bg-white border rounded-2xl p-6 space-y-4">

            <div class="flex justify-between">
                <div>
                    <div class="font-semibold text-lg">
                        {{ $v->vessel?->name }} — {{ $v->voyage_no }}
                    </div>
                    <div class="text-sm text-gray-500">
                        {{ $v->pol?->code }} → {{ $v->pod?->code }}
                    </div>
                </div>

                <div class="text-sm text-gray-600">
                    ATD: {{ optional($v->atd_at)->format('d M Y') }}
                </div>
            </div>

            <div class="grid grid-cols-5 gap-4 text-xs">

                @foreach ($v->milestones as $code => $date)
                    <div class="bg-gray-50 border rounded-lg p-3">
                        <div class="font-semibold uppercase">
                            {{ strtoupper($code) }}
                        </div>
                        <div class="text-gray-600">
                            {{ $date->format('d M Y') }}
                        </div>
                    </div>
                @endforeach

            </div>

        </div>

    @empty
        <div class="bg-white border rounded-xl p-6 text-center text-gray-500">
            Tidak ada kapal sailing.
        </div>
    @endforelse

</div>