<x-filament::section>
 <x-slot name="heading">{{ __('Status Kehadiran MP Hari Ini') }}</x-slot>

 <div class="space-y-3">
 @forelse($items as $it)
 @php
 $badge = match(strtolower($it['status'] ?? '')) {
 'present' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300',
 'leave' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-300',
 'sick' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',
 default => 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400',
 };
 @endphp
 <div class="rounded-xl border dark:border-slate-800 p-3 flex items-center justify-between">
 <div>
 <div class="font-medium text-slate-800 dark:text-slate-100">{{ $it['name'] }}</div>
 <div class="text-xs text-slate-500 dark:text-slate-400">{{ strtoupper($it['role']) }}</div>
 </div>
 <div class="flex items-center gap-3">
 <span class="px-2 py-0.5 text-xs rounded-full {{ $badge }}">
 {{ ucfirst($it['status'] ?? '—') }}
 </span>
 <span class="text-xs text-slate-500 dark:text-slate-400">{{ $it['time'] ?? '-' }}</span>
 </div>
 </div>
 @empty
 <div class="text-slate-500 dark:text-slate-400 text-sm">Belum ada data kehadiran hari ini.</div>
 @endforelse
 </div>
</x-filament::section>
