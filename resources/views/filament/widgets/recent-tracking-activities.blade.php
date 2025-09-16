<x-filament::section
    class="w-full col-span-full rounded-2xl ring-1 ring-gray-200 dark:ring-gray-800 shadow-sm bg-white dark:bg-gray-900">
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
        <div class="divide-y divide-gray-100 dark:divide-gray-800">
            @forelse ($activities as $act)
                @php
                    /** @var \Spatie\Activitylog\Models\Activity $act */
                    $track   = $act->subject instanceof \App\Models\ShipmentTrack ? $act->subject : null;
                    $ship    = $track?->shipment;
                    $props   = $act->properties?->toArray() ?? [];
                    $code    = $ship?->code ?? ($props['code'] ?? '-');
                    $user    = $act->causer?->name ?? 'Sistem';
                    $event   = $act->event;

                    $meta = [
                        'track_created'          => ['dibuat', 'bg-emerald-500'],
                        'track_status_changed'   => ['status diubah', 'bg-indigo-500'],
                        'track_location_changed' => ['lokasi diubah', 'bg-violet-500'],
                        'track_eta_changed'      => ['ETA diubah', 'bg-sky-500'],
                        'track_updated'          => ['diperbarui', 'bg-gray-500'],
                        'track_deleted'          => ['dihapus', 'bg-red-500'],
                        'track_restored'         => ['dipulihkan', 'bg-green-500'],
                    ];
                    [$label, $dot] = $meta[$event] ?? ['diperbarui', 'bg-gray-400'];

                    $initial = \Illuminate\Support\Str::of($user)->trim()->substr(0, 1)->upper();

                    $editUrl = $ship
                        ? \App\Filament\Resources\ShipmentResource::getUrl('edit', ['record' => $ship->getKey()])
                        : route('filament.admin.resources.shipments.index');

                    // status badge
                    $to        = $props['to'] ?? ($props['status'] ?? null);
                    $from      = $props['from'] ?? null;
                    $toLabel   = $props['to_label']   ?? ($props['status_label'] ?? null);
                    $fromLabel = $props['from_label'] ?? null;

                    $showStatusBadge = in_array($event, ['track_created', 'track_status_changed'], true) && $to;

                    // gunakan helper badgeColor dari widget
                    $badgeClass = \App\Filament\Resources\ShipmentTrackingResource\Widgets\RecentTrackingActivities::badgeColor($to);

                    $changedFields = $props['changed_fields'] ?? [];
                @endphp

                <div class="flex items-start gap-3 py-2.5 px-4 hover:bg-gray-50 dark:hover:bg-gray-800/30">
                    <span class="mt-2 h-2 w-2 rounded-full {{ $dot }}"></span>

                    <div class="flex-1 min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <a href="{{ $editUrl }}" target="_blank" rel="noopener"
                               class="font-mono text-xs px-2 py-0.5 rounded border border-gray-200 dark:border-gray-700 text-gray-800 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800/60">
                                {{ $code }}
                            </a>

                            <span class="text-xs text-gray-600 dark:text-gray-400">
                                {{ $label }} oleh
                            </span>

                            <span class="inline-flex items-center gap-1.5 pl-1.5 pr-2 py-0.5 rounded-full border border-gray-200 dark:border-gray-700">
                                <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-gray-200 dark:bg-gray-700 text-[10px] text-gray-800 dark:text-gray-200">
                                    {{ $initial }}
                                </span>
                                <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $user }}</span>
                            </span>

                            @if ($showStatusBadge)
                                <span class="text-[11px] ml-1 inline-flex items-center gap-1 px-2 py-0.5 rounded-full {{ $badgeClass }}">
                                    {{ $toLabel ?? strtoupper((string) $to) }}
                                </span>

                                @if ($event === 'track_status_changed' && $fromLabel)
                                    <span class="text-[11px] text-gray-500 dark:text-gray-400">
                                        (dari {{ $fromLabel }})
                                    </span>
                                @endif
                            @endif

                            @if ($event === 'track_location_changed')
                                <span class="text-[11px] text-gray-500 dark:text-gray-400">
                                    • Lokasi: {{ $props['from'] ?? '-' }} → {{ $props['to'] ?? '-' }}
                                </span>
                            @endif

                            @if ($event === 'track_eta_changed')
                                <span class="text-[11px] text-gray-500 dark:text-gray-400">
                                    • ETA:
                                    {{ $props['from'] ? \Illuminate\Support\Carbon::parse($props['from'])->isoFormat('D MMM YYYY, HH:mm') : '-' }}
                                    →
                                    {{ $props['to'] ? \Illuminate\Support\Carbon::parse($props['to'])->isoFormat('D MMM YYYY, HH:mm') : '-' }}
                                </span>
                            @endif

                            @if ($event === 'track_updated' && !empty($changedFields))
                                <span class="text-[11px] text-gray-500 dark:text-gray-400">
                                    • Field: {{ implode(', ', array_slice($changedFields, 0, 6)) }}@if (count($changedFields) > 6), …@endif
                                </span>
                            @endif
                        </div>

                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            {{ \Illuminate\Support\Carbon::parse($act->created_at)->calendar() }}
                            <span class="mx-1">•</span>
                            {{ \Illuminate\Support\Carbon::parse($act->created_at)->isoFormat('D MMM YYYY, HH:mm') }}
                        </div>
                    </div>
                </div>
            @empty
                <div class="py-3 px-4 text-sm text-gray-500">Belum ada aktivitas.</div>
            @endforelse
        </div>
    </div>
</x-filament::section>
