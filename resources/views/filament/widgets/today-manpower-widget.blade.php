<x-filament::section>
    <x-slot name="heading">{{ __('Status Kehadiran MP Hari Ini') }}</x-slot>

    <div class="space-y-3">
        @forelse($items as $it)
            @php
                $badge = match(strtolower($it['status'] ?? '')) {
                    'present' => 'bg-green-100 text-green-700',
                    'leave'   => 'bg-yellow-100 text-yellow-700',
                    'sick'    => 'bg-red-100 text-red-700',
                    default   => 'bg-slate-100 text-slate-600',
                };
            @endphp
            <div class="rounded-xl border p-3 flex items-center justify-between">
                <div>
                    <div class="font-medium text-slate-800">{{ $it['name'] }}</div>
                    <div class="text-xs text-slate-500">{{ strtoupper($it['role']) }}</div>
                </div>
                <div class="flex items-center gap-3">
                    <span class="px-2 py-0.5 text-xs rounded-full {{ $badge }}">
                        {{ ucfirst($it['status'] ?? '—') }}
                    </span>
                    <span class="text-xs text-slate-500">{{ $it['time'] ?? '-' }}</span>
                </div>
            </div>
        @empty
            <div class="text-slate-500 text-sm">Belum ada data kehadiran hari ini.</div>
        @endforelse
    </div>
</x-filament::section>
