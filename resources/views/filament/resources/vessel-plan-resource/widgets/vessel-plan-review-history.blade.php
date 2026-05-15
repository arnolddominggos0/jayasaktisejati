<div class="rounded-xl border dark:border-slate-800 bg-white dark:bg-slate-900 px-6 py-5 mb-6">
 <h3 class="text-base font-semibold mb-1 dark:text-white">
 Riwayat Review
 </h3>

 <p class="text-sm text-gray-500 dark:text-slate-400 mb-4">
 Jejak pengiriman draft, revisi, dan persetujuan final vessel plan.
 </p>

 @if (empty($entries))
 <div class="text-sm text-gray-500 dark:text-slate-400">
 Belum ada riwayat review.
 </div>
 @else
 <div class="space-y-3">
 @foreach ($entries as $entry)
 <div class="rounded-lg border dark:border-slate-800 p-4">
 <div class="flex items-center justify-between gap-4">
 <div class="flex items-center gap-3">
 <span class="inline-flex items-center rounded-full border dark:border-slate-700 px-3 py-1 text-xs font-medium {{ $entry['badge_color'] }}">
 {{ $entry['action'] }}
 </span>
 <div class="text-sm text-gray-600 dark:text-slate-400">
 {{ $entry['actor'] }} • {{ $entry['acted_at'] }}
 </div>
 </div>
 </div>

 @if (!empty($entry['note']))
 <div class="text-sm text-gray-800 dark:text-slate-200 mt-3">
 {{ $entry['note'] }}
 </div>
 @endif

 @if (!empty($entry['meta']))
 <div class="text-xs text-gray-500 dark:text-slate-400 mt-3">
 @foreach ($entry['meta'] as $key => $value)
 <div>{{ str_replace('_', ' ', ucfirst((string) $key)) }}: {{ is_array($value) ? json_encode($value) : $value }}</div>
 @endforeach
 </div>
 @endif
 </div>
 @endforeach
 </div>
 @endif
</div>
