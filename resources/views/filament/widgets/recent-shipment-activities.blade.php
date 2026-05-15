<div class="col-span-3">
 <x-filament::section
 class="w-full rounded-xl ring-1 ring-gray-200 dark:ring-slate-800 bg-white dark:bg-slate-900/80 dark:shadow-sm dark:shadow-black/10">
 <x-slot name="heading">
 <div class="flex items-center justify-between">
 <span>Aktivitas Terbaru</span>
 <a class="text-sm text-primary-600 hover:underline"
 href="{{ route('filament.admin.resources.shipments.index') }}">
 Lihat semua
 </a>
 </div>
 </x-slot>

 <div class="max-h-96 overflow-y-auto">
 @forelse ($groups as $group)
 <div class="px-4 pt-3 pb-2 sticky top-0 bg-white dark:bg-slate-900 z-10">
 <div class="text-xs font-semibold text-gray-700 dark:text-slate-300 tracking-wide">
 {{ $group['title'] }}
 </div>
 </div>

 <div class="divide-y divide-gray-100 dark:divide-slate-800">
 @foreach ($group['items'] as $it)
 <div class="flex items-start gap-3 py-2.5 px-4 hover:bg-gray-50 dark:hover:bg-slate-800/40">
 <span class="mt-2 h-2 w-2 rounded-full {{ $it['dotClass'] }}"></span>

 <div class="flex-1 min-w-0">
 <div class="flex flex-wrap items-center gap-2">
 <a href="{{ $it['editUrl'] }}" target="_blank" rel="noopener"
 class="font-mono text-xs px-2 py-0.5 rounded border border-gray-200 dark:border-slate-800 text-gray-800 dark:text-slate-200 hover:bg-gray-100 dark:hover:bg-slate-800/40">
 {{ $it['code'] }}
 </a>

 <x-filament::icon icon="{{ $it['icon'] }}" class="h-4 w-4 text-gray-500 dark:text-slate-400" />

 <span class="text-xs text-gray-600 dark:text-slate-400">
 {{ $it['eventLabel'] }} oleh
 </span>

 <span
 class="inline-flex items-center gap-1.5 pl-1.5 pr-2 py-0.5 rounded-full border border-gray-200 dark:border-slate-800">
 <span
 class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-gray-200 dark:bg-slate-700 text-[10px] text-gray-800 dark:text-slate-200">
 {{ $it['initial'] }}
 </span>
 <span
 class="text-sm font-medium text-gray-900 dark:text-slate-100">{{ $it['user'] }}</span>
 </span>

 @if ($it['showStatus'])
 <span
 class="text-[11px] ml-1 inline-flex items-center gap-1 px-2 py-0.5 rounded-full {{ $it['chipClass'] }}">
 {{ $it['toLabel'] }}
 </span>

 @if ($it['fromLabel'] && in_array($it['event'], ['status_changed', 'cancelled', 'uncancelled'], true))
 <span class="text-[11px] text-gray-500 dark:text-slate-400">
 (dari {{ $it['fromLabel'] }})
 </span>
 @endif
 @endif

 @if ($it['changedText'])
 <span
 class="ml-2 text-xs font-medium text-gray-700 dark:text-slate-300 bg-gray-100 dark:bg-slate-800 px-2 py-0.5 rounded-md">
 Kolom: {{ $it['changedText'] }}
 </span>
 @endif
 </div>

 <time title="{{ $it['fullTime'] }}"
 class="text-xs text-gray-500 dark:text-slate-400 mt-1 block">
 {{ $it['calendarTime'] }}
 </time>
 </div>
 </div>
 @endforeach
 </div>
 @empty
 <div class="py-3 px-4 text-sm text-gray-500 dark:text-slate-400">Belum ada aktivitas.</div>
 @endforelse
 </div>
 </x-filament::section>
</div>
