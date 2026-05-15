<x-filament::section
 class="w-full col-span-full rounded-xl ring-1 ring-gray-200 dark:ring-slate-800 bg-white dark:bg-slate-900/80 dark:shadow-sm dark:shadow-black/10">
 <x-slot name="heading">
 <div class="flex items-center justify-between">
 <span>Aktivitas Terbaru (Tracking)</span>
 <a class="text-sm text-primary-600 hover:underline"
 href="{{ route('filament.admin.resources.shipments.index') }}">
 Lihat semua
 </a>
 </div>
 </x-slot>

 <div class="max-h-96 overflow-y-auto">
 <div class="divide-y divide-gray-100 dark:divide-slate-800">
 @forelse ($activities as $act)
 @php
 /** @var \Spatie\Activitylog\Models\Activity $act */
 $track = $act->subject instanceof \App\Models\ShipmentTrack ? $act->subject : null;
 $ship = $track?->shipment;
 $props = $act->properties?->toArray() ?? [];
 $code = $ship?->code ?? ($props['code'] ?? '-');
 $user = $act->causer?->name ?? 'Sistem';
 $event = $act->event;

 $meta = [
 'track_created' => ['dibuat', 'bg-emerald-500'],
 'track_status_changed' => ['status diubah', 'bg-indigo-500'],
 'track_location_changed' => ['lokasi diubah', 'bg-violet-500'],
 'track_eta_changed' => ['ETA diubah', 'bg-sky-500'],
 'track_updated' => ['diperbarui', 'bg-gray-500'],
 'track_deleted' => ['dihapus', 'bg-red-500'],
 'track_restored' => ['dipulihkan', 'bg-green-500'],
 ];
 [$label, $dot] = $meta[$event] ?? ['diperbarui', 'bg-gray-400'];

 $initial = \Illuminate\Support\Str::of($user)->trim()->substr(0, 1)->upper();

 $editUrl = $ship
 ? \App\Filament\Resources\ShipmentResource::getUrl('edit', ['record' => $ship->getKey()])
 : route('filament.admin.resources.shipments.index');

 $to = $props['to'] ?? ($props['status'] ?? null);
 $from = $props['from'] ?? null;
 $toLabel = $props['to_label'] ?? ($props['status_label'] ?? null);
 $fromLabel = $props['from_label'] ?? null;

 $showStatusBadge = in_array($event, ['track_created', 'track_status_changed'], true) && $to;

 $badgeKey = \App\Filament\Resources\ShipmentTrackingResource\Widgets\RecentTrackingActivities::badgeColor(
 $to,
 );
 $badgeClass = match ($badgeKey) {
 'danger' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',
 'success' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300',
 'warning' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-300',
 'info' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
 default => 'bg-gray-100 dark:bg-slate-800 text-gray-700 dark:text-slate-300',
 };

 $changedFields = $props['changed_fields'] ?? [];

 $ts = \Illuminate\Support\Carbon::parse($act->created_at)->locale('id');
 $text = $ts->calendar();
 $full = $ts->translatedFormat('d F Y, H:i');
 @endphp

 <div class="flex items-start gap-3 py-2.5 px-4 hover:bg-gray-50 dark:hover:bg-slate-800/40">
 <span class="mt-2 h-2 w-2 rounded-full {{ $dot }}"></span>

 <div class="flex-1 min-w-0">
 <div class="flex flex-wrap items-center gap-2">
 <a href="{{ $editUrl }}" target="_blank" rel="noopener"
 class="font-mono text-xs px-2 py-0.5 rounded border border-gray-200 dark:border-slate-800 text-gray-800 dark:text-slate-200 hover:bg-gray-100 dark:hover:bg-slate-800/40">
 {{ $code }}
 </a>

 <span class="text-xs text-gray-600 dark:text-slate-400">
 {{ $label }} oleh
 </span>

 <span
 class="inline-flex items-center gap-1.5 pl-1.5 pr-2 py-0.5 rounded-full border border-gray-200 dark:border-slate-800">
 <span
 class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-gray-200 dark:bg-slate-700 text-[10px] text-gray-800 dark:text-slate-200">
 {{ $initial }}
 </span>
 <span
 class="text-sm font-medium text-gray-900 dark:text-slate-100">{{ $user }}</span>
 </span>

 @if ($showStatusBadge)
 <span
 class="text-[11px] ml-1 inline-flex items-center gap-1 px-2 py-0.5 rounded-full {{ $badgeClass }}">
 {{ $toLabel ?? strtoupper((string) $to) }}
 </span>

 @if ($event === 'track_status_changed' && $fromLabel)
 <span class="text-[11px] text-gray-500 dark:text-slate-400">
 (dari {{ $fromLabel }})
 </span>
 @endif
 @endif

 @if ($event === 'track_location_changed')
 <span class="text-[11px] text-gray-500 dark:text-slate-400">
 — Lokasi: {{ $props['from'] ?? '-' }} → {{ $props['to'] ?? '-' }}
 </span>
 @endif

 @if ($event === 'track_eta_changed')
 <span class="text-[11px] text-gray-500 dark:text-slate-400">
 — ETA:
 {{ $props['from'] ? \Illuminate\Support\Carbon::parse($props['from'])->locale('id')->translatedFormat('d F Y, H:i') : '-' }}
 →
 {{ $props['to'] ? \Illuminate\Support\Carbon::parse($props['to'])->locale('id')->translatedFormat('d F Y, H:i') : '-' }}
 </span>
 @endif

 @if ($event === 'track_updated' && !empty($changedFields))
 <span class="text-[11px] text-gray-500 dark:text-slate-400">
 — Field: {{ implode(', ', array_slice($changedFields, 0, 6)) }}@if (count($changedFields) > 6)
 , ...
 @endif
 </span>
 @endif
 </div>

 <time datetime="{{ $ts->toIso8601String() }}" title="{{ $full }}"
 class="text-xs text-gray-500 dark:text-slate-400 mt-1 block">
 {{ $text }}
 </time>
 </div>
 </div>
 @empty
 <div class="py-3 px-4 text-sm text-gray-500 dark:text-slate-400">Belum ada aktivitas.</div>
 @endforelse
 </div>
 </div>
 </x-filament::section>
