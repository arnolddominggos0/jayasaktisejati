<div class="col-span-3">
    <x-filament::section
        class="w-full rounded-2xl ring-1 ring-gray-200 dark:ring-gray-800 shadow-sm bg-white dark:bg-gray-900">
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
            <div class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse ($activities as $act)
                    @php
                        // Ambil subject & properti activity
                        $shipment = $act->subject instanceof \App\Models\Shipment ? $act->subject : null;
                        $props = $act->properties?->toArray() ?? [];
                        $code = $shipment?->code ?? ($props['code'] ?? '-');
                        $user = $act->causer?->name ?? 'Sistem';
                        $event = $act->event;

                        // Normalisasi teks (enum/string -> Title Case)
                        $normalize = function ($v) {
                            if ($v instanceof \BackedEnum) {
                                $v = $v->value;
                            }
                            return \Illuminate\Support\Str::of((string) $v)->replace('_', ' ')->title();
                        };

                        $fromText = isset($props['from']) ? $normalize($props['from']) : null;
                        $toText = isset($props['to']) ? $normalize($props['to']) : null;

                        $fromColor = \App\Filament\Resources\ShipmentResource\Widgets\RecentShipmentActivities::badgeColor(
                            $props['from'] ?? null,
                        );
                        $toColor = \App\Filament\Resources\ShipmentResource\Widgets\RecentShipmentActivities::badgeColor(
                            $props['to'] ?? null,
                        );

                        $editUrl = $shipment
                            ? \App\Filament\Resources\ShipmentResource::getUrl('edit', [
                                'record' => $shipment->getKey(),
                            ])
                            : route('filament.admin.resources.shipments.index');
                    @endphp

                    <div class="flex justify-between gap-4 py-3 px-4 hover:bg-gray-50 dark:hover:bg-gray-800/40">
                        <div class="flex-1 pr-2 text-sm text-gray-900 dark:text-gray-100 whitespace-normal break-words">
                            <a href="{{ $editUrl }}" class="font-medium hover:underline" target="_blank"
                                rel="noopener">
                                {{ $code }}
                            </a>

                            @switch($event)
                                @case('created')
                                    <span> dibuat oleh </span><span class="font-medium">{{ $user }}</span>
                                @break

                                @case('status_changed')
                                    <span> status diubah dari </span>
                                    <x-filament::badge :color="$fromColor">{{ $fromText }}</x-filament::badge>
                                    <span> → </span>
                                    <x-filament::badge :color="$toColor">{{ $toText }}</x-filament::badge>
                                    <span> oleh </span><span class="font-medium">{{ $user }}</span>
                                @break

                                @case('route_updated')
                                    <span> rute diperbarui oleh </span><span class="font-medium">{{ $user }}</span>
                                @break

                                @case('deleted')
                                    <span> dihapus oleh </span><span class="font-medium">{{ $user }}</span>
                                @break

                                @case('restored')
                                    <span> dipulihkan oleh </span><span class="font-medium">{{ $user }}</span>
                                @break

                                @default
                                    <span> diperbarui oleh </span><span class="font-medium">{{ $user }}</span>
                            @endswitch

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
    </div>
