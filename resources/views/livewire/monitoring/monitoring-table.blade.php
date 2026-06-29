<div class="jss-mon-table-wrap">
    {{-- No inner card: table renders inside the unified workspace card in pelacakan-monitoring.blade.php --}}
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">

            {{-- ── Table Header ── --}}
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Unit</th>
                    <th scope="col" class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">SPPB</th>
                    <th scope="col" class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Route</th>
                    <th scope="col" class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Stage</th>
                    <th scope="col" class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Progress</th>
                    <th scope="col" class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Age</th>
                    <th scope="col" class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Exception</th>
                    <th scope="col" class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Voyage</th>
                    <th scope="col" class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">ETA</th>
                </tr>
            </thead>

            {{-- ── Table Body ── --}}
            <tbody class="divide-y divide-gray-100 bg-white">

                {{--
                    Sprint 6.3A — Placeholder body.
                    Actual row rendering is Sprint 6.3B.
                    Skeleton rows convey column structure and loading state.
                --}}
                @if ($totalRows > 0)

                    {{-- Skeleton rows: one per available page-row slot --}}
                    @foreach (range(1, min($perPage, 8)) as $_)
                    <tr class="animate-pulse">
                        {{-- Unit --}}
                        <td class="px-3 py-3">
                            <div class="space-y-1.5">
                                <div class="h-3 w-28 rounded bg-gray-200"></div>
                                <div class="h-2.5 w-20 rounded bg-gray-100"></div>
                            </div>
                        </td>
                        {{-- SPPB --}}
                        <td class="px-3 py-3">
                            <div class="space-y-1.5">
                                <div class="h-3 w-32 rounded bg-gray-200"></div>
                                <div class="h-2.5 w-24 rounded bg-gray-100"></div>
                            </div>
                        </td>
                        {{-- Route --}}
                        <td class="px-3 py-3">
                            <div class="h-3 w-16 rounded bg-gray-200"></div>
                        </td>
                        {{-- Stage --}}
                        <td class="px-3 py-3">
                            <div class="h-5 w-24 rounded-full bg-gray-200"></div>
                        </td>
                        {{-- Progress --}}
                        <td class="px-3 py-3">
                            <div class="flex items-center gap-2">
                                <div class="h-2 w-16 rounded-full bg-gray-200"></div>
                                <div class="h-2.5 w-8 rounded bg-gray-100"></div>
                            </div>
                        </td>
                        {{-- Age --}}
                        <td class="px-3 py-3">
                            <div class="h-3 w-10 rounded bg-gray-200"></div>
                        </td>
                        {{-- Exception --}}
                        <td class="px-3 py-3">
                            <div class="h-4 w-14 rounded-full bg-gray-100"></div>
                        </td>
                        {{-- Voyage --}}
                        <td class="px-3 py-3">
                            <div class="space-y-1.5">
                                <div class="h-3 w-12 rounded bg-gray-200"></div>
                                <div class="h-2.5 w-20 rounded bg-gray-100"></div>
                            </div>
                        </td>
                        {{-- ETA --}}
                        <td class="px-3 py-3">
                            <div class="h-3 w-20 rounded bg-gray-200"></div>
                        </td>
                    </tr>
                    @endforeach

                @else

                    {{-- Empty state --}}
                    <tr>
                        <td colspan="9">
                            <div class="flex h-64 flex-col items-center justify-center gap-2">
                                <x-heroicon-o-signal-slash class="size-8 text-gray-200" />
                                <p class="text-sm font-semibold text-gray-400">Tidak ada unit yang sedang dipantau</p>
                                <p class="text-xs text-gray-300">Periksa filter aktif atau tekan Refresh.</p>
                            </div>
                        </td>
                    </tr>

                @endif

            </tbody>
        </table>
    </div>

    {{-- Pagination indicator (Sprint 6.3B will add $rows->links()) --}}
    @if ($totalRows > 0 && $lastPage > 1)
        <div class="px-4 pb-3 pt-2">
            <p class="text-xs text-gray-400">Hal {{ $currentPage }}/{{ $lastPage }}</p>
        </div>
    @endif

    {{-- Row count indicator --}}
    @if ($totalRows > 0)
        <p class="px-4 pb-3 text-right text-xs text-gray-400">
            {{ $totalRows }} unit tersedia · rendering Sprint 6.3B
        </p>
    @endif

</div>
