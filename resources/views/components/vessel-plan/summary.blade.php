<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">

    <div class="rounded-xl border bg-white dark:bg-slate-900 p-4">
        <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-slate-400">Jumlah Kapal</div>
        <div class="mt-1 text-2xl font-semibold">{{ $total }}</div>
    </div>

    <div class="rounded-xl border bg-white dark:bg-slate-900 p-4">
        <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-slate-400">Max ETD Gap</div>
        <div class="mt-1 text-2xl font-semibold">{{ $maxGap }} hari</div>
        <div class="text-xs text-gray-500 dark:text-slate-400">ETD ke ETD</div>
    </div>

    <div class="rounded-xl border bg-white dark:bg-slate-900 p-4">
        <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-slate-400">Batas SOP</div>
        <div class="mt-1 text-2xl font-semibold">{{ $idealGap }} hari</div>
    </div>

    <div class="rounded-xl border bg-white dark:bg-slate-900 p-4">
        <div class="text-xs uppercase tracking-wide text-gray-500 dark:text-slate-400">Status Analisa</div>
        <div class="mt-1 text-2xl font-semibold {{ $statusColor }}">
            {{ $statusLabel }}
        </div>
    </div>

</div>
    