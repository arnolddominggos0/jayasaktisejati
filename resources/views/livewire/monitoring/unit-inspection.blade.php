@if ($inspection && !empty($inspection->stages))
    <div class="space-y-2">
        @foreach ($inspection->stages as $stage)
            <div class="mon-insp-row">
                <div class="flex flex-col">
                    <span class="mon-table font-medium text-gray-700">{{ $stage->stage_label }}</span>
                    @if ($stage->summary_1line && $stage->gate_decision !== 'accept')
                        <span class="mon-caption text-amber-600">{{ $stage->summary_1line }}</span>
                    @endif
                </div>
                <div class="flex items-center gap-2">
                    @if ($stage->ng_count > 0)
                        <span class="mon-badge mon-badge-danger">{{ $stage->ng_count }} NG</span>
                    @endif
                    <span class="mon-badge {{ $stage->status === 'passed' ? 'mon-badge-success' : ($stage->status === 'failed' ? 'mon-badge-danger' : ($stage->status === 'pending' ? 'mon-badge-warning' : 'mon-badge-neutral')) }}">
                        {{ $stage->status === 'passed' ? 'OK' : ($stage->status === 'failed' ? 'NG' : ($stage->status === 'pending' ? 'Menunggu' : 'Belum ada')) }}
                    </span>
                    @if ($stage->gate_decision)
                        <span class="mon-caption font-semibold {{ $stage->gate_decision === 'accept' ? 'text-emerald-600' : ($stage->gate_decision === 'return_to_pdc' ? 'text-red-600' : 'text-amber-600') }}">
                            {{ ucfirst(str_replace('_', ' ', $stage->gate_decision)) }}
                        </span>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
@else
    <p class="mon-caption">Belum ada data inspeksi.</p>
@endif