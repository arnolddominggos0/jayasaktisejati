<x-filament::section>
    <x-slot name="heading">{{ __('Armada Aktif') }}</x-slot>

    <div class="space-y-3">
        @forelse($rows as $r)
            <div class="rounded-xl border p-3">
                <div class="flex items-center justify-between">
                    <div class="font-medium text-slate-800">{{ $r['name'] }}</div>
                    <span class="text-[11px] px-2 py-0.5 rounded-full bg-blue-100 text-blue-700">
                        {{ strtoupper($r['badge'] ?? '-') }}
                    </span>
                </div>
                <div class="mt-1 text-xs text-slate-500">{{ $r['route'] }}</div>
                @if ($r['eta'])
                    <div class="mt-0.5 text-xs text-slate-400">ETA: {{ $r['eta'] }}</div>
                @endif
            </div>
        @empty
            <div class="text-slate-500 text-sm">Tidak ada armada aktif.</div>
        @endforelse
    </div>
</x-filament::section>
