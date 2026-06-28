<div>{{-- Livewire root element: always present; content renders only when unit is loaded --}}
@if ($unitDetail)
<div class="jss-mon-detail-slide space-y-4">

    {{-- Progress Section --}}
    <div class="rounded-xl border border-gray-200 bg-white p-4">
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-900">Progress</h3>
            <span class="text-sm text-gray-500">{{ $unitDetail->age->label }}</span>
        </div>
        <div class="mt-3 flex items-center gap-3">
            <div class="h-3 flex-1 overflow-hidden rounded-full bg-gray-200">
                <div class="h-full rounded-full bg-blue-500" style="width: {{ $unitDetail->progress_pct }}%"></div>
            </div>
            <span class="text-sm font-medium text-gray-700">{{ $unitDetail->progress_pct }}%</span>
        </div>
        <p class="mt-2 text-sm text-gray-500">Stage: {{ $unitDetail->stage->stage_label }}</p>
    </div>

    {{-- Timeline Section --}}
    <div class="rounded-xl border border-gray-200 bg-white p-4">
        <h3 class="text-lg font-semibold text-gray-900 mb-3">Timeline</h3>
        @include('livewire.monitoring.unit-timeline', ['timeline' => $unitDetail->timeline])
    </div>

    {{-- Inspection Section --}}
    <div class="rounded-xl border border-gray-200 bg-white p-4">
        <h3 class="text-lg font-semibold text-gray-900 mb-3">Inspeksi</h3>
        @include('livewire.monitoring.unit-inspection', ['inspection' => $unitDetail->inspection])
    </div>

    {{-- Lead Time Section --}}
    @if ($unitDetail->lead_time)
        <div class="rounded-xl border border-gray-200 bg-white p-4">
            <h3 class="text-lg font-semibold text-gray-900">Lead Time KPI</h3>
            <p class="mt-1 text-sm text-gray-500">{{ $unitDetail->lead_time->summary_text ?? '—' }}</p>
        </div>
    @endif

    {{-- Administrative Info --}}
    <div class="rounded-xl border border-gray-200 bg-white p-4">
        <h3 class="text-lg font-semibold text-gray-900 mb-3">Informasi Administratif</h3>
        <dl class="grid grid-cols-2 gap-3 text-sm">
            <dt class="text-gray-400">Vessel</dt>
            <dd class="text-gray-700">{{ $unitDetail->admin->vessel_name ?? '—' }}</dd>
            <dt class="text-gray-400">Voyage No</dt>
            <dd class="text-gray-700">{{ $unitDetail->admin->voyage_no ?? '—' }}</dd>
            <dt class="text-gray-400">ETD</dt>
            <dd class="text-gray-700">{{ $unitDetail->admin->etd?->format('d M Y H:i') ?? '—' }}</dd>
            <dt class="text-gray-400">ETA</dt>
            <dd class="text-gray-700">{{ $unitDetail->admin->eta?->format('d M Y H:i') ?? '—' }}</dd>
            <dt class="text-gray-400">POL</dt>
            <dd class="text-gray-700">{{ $unitDetail->admin->pol_name ?? '—' }}</dd>
            <dt class="text-gray-400">POD</dt>
            <dd class="text-gray-700">{{ $unitDetail->admin->pod_name ?? '—' }}</dd>
        </dl>
    </div>

    {{-- Sibling Units Section --}}
    @if (!empty($unitDetail->sibling_units))
        <div class="rounded-xl border border-gray-200 bg-white p-4">
            <h3 class="text-lg font-semibold text-gray-900 mb-3">Unit dalam SPPB</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr>
                            <th class="px-2 py-2 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Reg No</th>
                            <th class="px-2 py-2 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Model</th>
                            <th class="px-2 py-2 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Warna</th>
                            <th class="px-2 py-2 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Container</th>
                            <th class="px-2 py-2 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Inspeksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($unitDetail->sibling_units as $sibling)
                            <tr class="{{ $sibling->has_ng ? 'bg-red-50' : '' }}">
                                <td class="px-2 py-2 text-sm font-medium text-gray-900">{{ $sibling->reg_no ?? '—' }}</td>
                                <td class="px-2 py-2 text-sm text-gray-600">{{ $sibling->model_no ?? '—' }}</td>
                                <td class="px-2 py-2 text-sm text-gray-600">{{ $sibling->color ?? '—' }}</td>
                                <td class="px-2 py-2 text-sm text-gray-600">{{ $sibling->container_display ?? '—' }}</td>
                                <td class="px-2 py-2 text-sm">
                                    @if ($sibling->has_ng)
                                        <span class="inline-flex items-center rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-700">NG</span>
                                    @elseif ($sibling->inspection_status)
                                        <span class="inline-flex items-center rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700">{{ $sibling->inspection_status }}</span>
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
                <a href="{{ $link->url }}" class="inline-flex items-center gap-2 rounded-lg bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-200">
                    {{ $link->label }}
                </a>
            @endforeach
        </div>
    @endif

</div>
@endif
</div>