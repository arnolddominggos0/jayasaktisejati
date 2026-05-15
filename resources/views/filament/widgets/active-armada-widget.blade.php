<x-filament::section>
 <x-slot name="heading">{{ __('Armada Aktif') }}</x-slot>

 <div class="space-y-3">
 @forelse($rows as $r)
 <div class="rounded-xl border dark:border-slate-800 p-3">
 <div class="flex items-center justify-between">
 <div class="font-medium text-slate-800 dark:text-slate-100">{{ $r['name'] }}</div>
 <span class="text-[11px] px-2 py-0.5 rounded-full bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300">
 {{ strtoupper($r['badge'] ?? '-') }}
 </span>
 </div>
 <div class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ $r['route'] }}</div>
 @if ($r['eta'])
 <div class="mt-0.5 text-xs text-slate-400 dark:text-slate-500">ETA: {{ $r['eta'] }}</div>
 @endif
 </div>
 @empty
 <div class="text-slate-500 dark:text-slate-400 text-sm">Tidak ada armada aktif.</div>
 @endforelse
 </div>
</x-filament::section>
