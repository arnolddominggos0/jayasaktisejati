@php $units = $getState(); @endphp

@if ($units->isEmpty())
    <p class="text-sm text-gray-500 dark:text-gray-400 italic">Tidak ada data unit.</p>
@else
    <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
            <thead class="bg-gray-50 dark:bg-gray-800">
                <tr>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider w-8">#</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Model</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Container</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">No. Rangka</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Warna</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">No. DO / SPPB</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700 bg-white dark:bg-gray-900">
                @foreach ($units as $i => $unit)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                        <td class="px-3 py-2 text-gray-400 dark:text-gray-500 text-xs">{{ $i + 1 }}</td>
                        <td class="px-3 py-2 font-semibold text-gray-900 dark:text-white">{{ $unit->model_no ?? '—' }}</td>
                        <td class="px-3 py-2 font-mono text-xs text-gray-600 dark:text-gray-300">{{ $unit->container_display ?? '—' }}</td>
                        <td class="px-3 py-2 font-mono text-xs text-gray-500 dark:text-gray-400">{{ $unit->chassis_no ?? '—' }}</td>
                        <td class="px-3 py-2 text-gray-600 dark:text-gray-300">{{ $unit->color ?? '—' }}</td>
                        <td class="px-3 py-2 text-xs text-gray-500 dark:text-gray-400 font-mono">{{ $unit->do_number ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot class="bg-gray-50 dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700">
                <tr>
                    <td colspan="6" class="px-3 py-1.5 text-xs text-gray-400 dark:text-gray-500">
                        {{ $units->count() }} unit · read-only archive
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
@endif
