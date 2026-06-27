<div class="jss-mon-table-wrap">
    @if ($rows && $rows->isNotEmpty())
        {{-- Table skeleton --}}
        <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Unit</th>
                        <th class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">SPPB</th>
                        <th class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Route</th>
                        <th class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Stage</th>
                        <th class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Progress</th>
                        <th class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Age</th>
                        <th class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Exceptions</th>
                        <th class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Voyage</th>
                        <th class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">ETA</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    @php
                    $pageIndex = ($rows->currentPage() - 1) * $rows->perPage();
                    @endphp
                    @foreach ($rows as $index => $row)
                        <tr
                            class="cursor-pointer transition hover:bg-gray-50 {{ $row->is_search_match ? 'bg-yellow-50' : '' }}"
                            wire:click="openDetail({{ $row->unit_id ?? $row->shipment_id }})"
                        >
                            {{-- Unit identity --}}
                            <td class="px-3 py-3">
                                <div class="flex flex-col">
                                    <span class="text-sm font-semibold text-gray-900">{{ $row->unit_reg_no ?? '—' }}</span>
                                    <span class="text-xs text-gray-400">{{ $row->unit_model_no ?? '—' }}</span>
                                </div>
                            </td>
                            {{-- SPPB --}}
                            <td class="px-3 py-3">
                                <div class="flex flex-col">
                                    <span class="text-sm text-gray-700">{{ $row->shipment_code }}</span>
                                    <span class="text-xs text-gray-400">{{ $row->customer_name }}</span>
                                </div>
                            </td>
                            {{-- Route --}}
                            <td class="px-3 py-3">
                                <span class="text-sm text-gray-600">{{ $row->route_label }}</span>
                            </td>
                            {{-- Stage --}}
                            <td class="px-3 py-3">
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-700">
                                    {{ $row->stage->stage_label }}
                                </span>
                            </td>
                            {{-- Progress --}}
                            <td class="px-3 py-3">
                                <div class="flex items-center gap-2">
                                    <div class="h-2 w-16 overflow-hidden rounded-full bg-gray-200">
                                        <div class="h-full rounded-full bg-blue-500" style="width: {{ $row->progress_pct }}%"></div>
                                    </div>
                                    <span class="text-xs text-gray-400">{{ $row->progress_pct }}%</span>
                                </div>
                            </td>
                            {{-- Age --}}
                            <td class="px-3 py-3">
                                <span class="text-sm {{ $row->age->is_stuck ? 'font-semibold text-red-600' : 'text-gray-600' }}">
                                    {{ $row->age->label }}
                                </span>
                            </td>
                            {{-- Exceptions --}}
                            <td class="px-3 py-3">
                                <div class="flex flex-wrap gap-1">
                                    @forelse ($row->exceptions as $exc)
                                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $exc->severity === 'critical' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700' }}">
                                            {{ $exc->label }}
                                        </span>
                                    @empty
                                        <span class="text-xs text-gray-300">—</span>
                                    @endforelse
                                </div>
                            </td>
                            {{-- Voyage --}}
                            <td class="px-3 py-3">
                                <div class="flex flex-col">
                                    <span class="text-sm text-gray-600">{{ $row->voyage_no ?? '—' }}</span>
                                    <span class="text-xs text-gray-400">{{ $row->vessel_name ?? '' }}</span>
                                </div>
                            </td>
                            {{-- ETA --}}
                            <td class="px-3 py-3">
                                <span class="text-sm text-gray-600">{{ $row->eta_label ?? '—' }}</span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        {{ $rows->links() }}

    @else
        {{-- Empty state --}}
        <div class="flex flex-col items-center justify-center gap-3 rounded-xl border border-gray-200 bg-white py-16">
            <x-heroicon-o-magnifying-glass class="size-12 text-gray-300" />
            <p class="text-sm font-medium text-gray-500">Tidak ada unit yang dipantau</p>
            <p class="text-xs text-gray-400">Coba ubah filter atau muat ulang</p>
        </div>
    @endif
</div>