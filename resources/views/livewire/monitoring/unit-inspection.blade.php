@if ($inspection && !empty($inspection->stages))
    <div class="space-y-3">
        @foreach ($inspection->stages as $stage)
            <div class="flex items-center justify-between rounded-lg border border-gray-100 px-3 py-2">
                <div class="flex flex-col">
                    <span class="text-sm font-medium text-gray-700">{{ $stage->stage_label }}</span>
                    @if ($stage->summary_1line && $stage->gate_decision !== 'accept')
                        <span class="text-xs text-amber-600">{{ $stage->summary_1line }}</span>
                    @endif
                </div>
                <div class="flex items-center gap-2">
                    @if ($stage->ng_count > 0)
                        <span class="inline-flex items-center rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-700">
                            {{ $stage->ng_count }} NG
                        </span>
                    @endif
                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $stage->status === 'passed' ? 'bg-green-100 text-green-700' : ($stage->status === 'failed' ? 'bg-red-100 text-red-700' : ($stage->status === 'pending' ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-500')) }}">
                        {{ $stage->status === 'passed' ? 'OK' : ($stage->status === 'failed' ? 'NG' : ($stage->status === 'pending' ? 'Menunggu' : 'Belum ada')) }}
                    </span>
                    @if ($stage->gate_decision)
                        <span class="text-xs {{ $stage->gate_decision === 'accept' ? 'text-green-600' : ($stage->gate_decision === 'return_to_pdc' ? 'text-red-600' : 'text-amber-600') }}">
                            {{ ucfirst(str_replace('_', ' ', $stage->gate_decision)) }}
                        </span>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
@else
    <p class="text-sm text-gray-400">Belum ada data inspeksi.</p>
@endif