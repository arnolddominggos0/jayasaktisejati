<div class="grid grid-cols-5 gap-4">
    <div class="rounded-xl border bg-white p-4">
        <div class="text-xs text-gray-500 uppercase tracking-wide">
            Total Pelayaran
        </div>
        <div class="text-2xl font-bold">
            {{ $kpi['total'] ?? 0 }}
        </div>
    </div>

    <div class="rounded-xl border bg-white p-4">
        <div class="text-xs text-gray-500 uppercase tracking-wide">
            Tepat Waktu
        </div>
        <div class="text-2xl font-bold text-green-600">
            {{ $kpi['ontime'] ?? 0 }}
        </div>
    </div>

    <div class="rounded-xl border bg-white p-4">
        <div class="text-xs text-gray-500 uppercase tracking-wide">
            Terlambat
        </div>
        <div class="text-2xl font-bold text-red-600">
            {{ $kpi['late'] ?? 0 }}
        </div>
    </div>

    <div class="rounded-xl border bg-white p-4">
        <div class="text-xs text-gray-500 uppercase tracking-wide">
            Kepatuhan SLA
        </div>
        <div class="text-2xl font-bold">
            {{ ($kpi['total'] ?? 0) > 0
                ? round($kpi['ontime'] / $kpi['total'] * 100, 2).' %'
                : '—' }}
        </div>
    </div>

    <div class="rounded-xl border bg-white p-4">
        <div class="text-xs text-gray-500 uppercase tracking-wide">
            Rata-rata Pelayaran
        </div>
        <div class="text-2xl font-bold">
            {{ collect($kpi['rows'] ?? [])->avg('actual_days') !== null
                ? number_format(collect($kpi['rows'])->avg('actual_days'), 2).' hari'
                : '—' }}
        </div>
    </div>
</div>
