<div>{{-- Livewire root element: always present; content renders only when unit is loaded --}}
@if ($unitDetail)
<div class="jss-mon-detail-slide space-y-4">

    {{-- Progress Section --}}
    <div class="mon-panel">
        <div class="mon-panel-head">
            <h3 class="mon-panel-title">Progress</h3>
            <span class="mon-caption">{{ $unitDetail->age->label }}</span>
        </div>
        <div class="mt-2 flex items-center gap-3">
            <div class="mon-progress flex-1">
                <div class="mon-progress-fill" style="width: {{ $unitDetail->progress_pct }}%"></div>
            </div>
            <span class="mon-pct">{{ $unitDetail->progress_pct }}%</span>
        </div>
        <p class="mt-2 mon-caption">Stage: {{ $unitDetail->stage->stage_label }}</p>
    </div>

    {{-- Timeline Section --}}
    <div class="mon-panel">
        <div class="mon-panel-head">
            <h3 class="mon-panel-title">Timeline</h3>
        </div>
        @include('livewire.monitoring.unit-timeline', ['timeline' => $unitDetail->timeline])
    </div>

    {{-- Inspection Section --}}
    <div class="mon-panel">
        <div class="mon-panel-head">
            <h3 class="mon-panel-title">Inspeksi</h3>
        </div>
        @include('livewire.monitoring.unit-inspection', ['inspection' => $unitDetail->inspection])
    </div>

    {{-- Lead Time Section --}}
    @if ($unitDetail->lead_time)
        <div class="mon-panel">
            <div class="mon-panel-head">
                <h3 class="mon-panel-title">Lead Time KPI</h3>
            </div>
            <p class="mon-caption">{{ $unitDetail->lead_time->summary_text ?? '—' }}</p>
        </div>
    @endif

    {{-- Administrative Info --}}
    <div class="mon-panel">
        <div class="mon-panel-head">
            <h3 class="mon-panel-title">Informasi Administratif</h3>
        </div>
        <dl class="grid grid-cols-2 gap-x-4 gap-y-2.5">
            <dt class="mon-foot font-medium" style="color: var(--mon-neutral-400);">Vessel</dt>
            <dd class="mon-table" style="color: var(--mon-navy-600); font-weight: 600;">{{ $unitDetail->admin->vessel_name ?? '—' }}</dd>
            <dt class="mon-foot font-medium" style="color: var(--mon-neutral-400);">Voyage No</dt>
            <dd class="mon-table" style="color: var(--mon-navy-600); font-weight: 600;">{{ $unitDetail->admin->voyage_no ?? '—' }}</dd>
            <dt class="mon-foot font-medium" style="color: var(--mon-neutral-400);">ETD</dt>
            <dd class="mon-table">{{ $unitDetail->admin->etd?->format('d M Y H:i') ?? '—' }}</dd>
            <dt class="mon-foot font-medium" style="color: var(--mon-neutral-400);">ETA</dt>
            <dd class="mon-table">{{ $unitDetail->admin->eta?->format('d M Y H:i') ?? '—' }}</dd>
            <dt class="mon-foot font-medium" style="color: var(--mon-neutral-400);">POL</dt>
            <dd class="mon-table">{{ $unitDetail->admin->pol_name ?? '—' }}</dd>
            <dt class="mon-foot font-medium" style="color: var(--mon-neutral-400);">POD</dt>
            <dd class="mon-table">{{ $unitDetail->admin->pod_name ?? '—' }}</dd>
        </dl>
    </div>

    {{-- Sibling Units Section --}}
    @if (!empty($unitDetail->sibling_units))
        <div class="mon-panel">
            <div class="mon-panel-head">
                <h3 class="mon-panel-title">Unit dalam SPPB</h3>
            </div>
            <div class="overflow-x-auto -mx-2">
                <table class="mon-table">
                    <thead>
                        <tr>
                            <th>Reg No</th>
                            <th>Model</th>
                            <th>Warna</th>
                            <th>Container</th>
                            <th>Inspeksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($unitDetail->sibling_units as $sibling)
                            <tr class="{{ $sibling->has_ng ? 'bg-red-50/60' : '' }}">
                                <td><span class="mon-unit-code">{{ $sibling->reg_no ?? '—' }}</span></td>
                                <td class="text-gray-600">{{ $sibling->model_no ?? '—' }}</td>
                                <td class="text-gray-600">{{ $sibling->color ?? '—' }}</td>
                                <td class="text-gray-600">{{ $sibling->container_display ?? '—' }}</td>
                                <td>
                                    @if ($sibling->has_ng)
                                        <span class="mon-badge mon-badge-danger">NG</span>
                                    @elseif ($sibling->inspection_status)
                                        <span class="mon-badge mon-badge-success">{{ $sibling->inspection_status }}</span>
                                    @else
                                        <span class="text-gray-300">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- Deep Links Section --}}
    @if (!empty($unitDetail->deep_links))
        <div class="flex flex-wrap gap-2">
            @foreach ($unitDetail->deep_links as $link)
                <a href="{{ $link->url }}" class="mon-deeplink">
                    {{ $link->label }}
                    <x-heroicon-o-arrow-top-right-on-square class="w-3.5 h-3.5" />
                </a>
            @endforeach
        </div>
    @endif

</div>
@endif
</div>