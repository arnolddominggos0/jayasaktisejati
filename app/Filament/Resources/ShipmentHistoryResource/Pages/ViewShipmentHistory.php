<?php

namespace App\Filament\Resources\ShipmentHistoryResource\Pages;

use App\Filament\Resources\ShipmentHistoryResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\{Section, TextEntry, IconEntry, ViewEntry};
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;

class ViewShipmentHistory extends ViewRecord
{
    protected static string $resource = ShipmentHistoryResource::class;

    protected function getHeaderActions(): array
    {
        $record = $this->record;

        return [
            Action::make('print_resi')
                ->label('Cetak Resi')
                ->icon('heroicon-o-printer')
                ->url(route('shipments.resi', ['shipment' => $record->id]) . '?download=1')
                ->openUrlInNewTab(),

            Action::make('timeline')
                ->label('Kelola Timeline')
                ->icon('heroicon-o-sparkles')
                ->color('primary')
                ->url(\App\Filament\Resources\ShipmentTrackingResource::getUrl('manage', ['record' => $record]))
                ->visible(fn() => auth_user()?->hasRole('super_admin') === true),

            Action::make('edit')
                ->label('Edit')
                ->icon('heroicon-o-pencil-square')
                ->color('warning')
                ->url(\App\Filament\Resources\ShipmentResource::getUrl('edit', ['record' => $record]))
                ->visible(fn() => auth_user()?->hasRole('super_admin') === true),

            Action::make('copy_link')
                ->label('Salin Link')
                ->icon('heroicon-o-link')
                ->action(
                    fn() => Notification::make()
                        ->title('Link disalin')
                        ->success()
                        ->send()
                )
                ->extraAttributes([
                    'x-data' => '{}',
                    'x-on:click.stop' => 'navigator.clipboard.writeText(window.location.href)',
                ])
                ->color('gray'),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        $trackTime = function ($record, array $statuses, string $direction = 'asc') {
            $q = null;
            if (method_exists($record, 'tracks')) {
                $q = $record->tracks();
            } elseif (method_exists($record, 'shipmentTracks')) {
                $q = $record->shipmentTracks();
            } else {
                return null;
            }

            $q = $q->whereIn('status', $statuses)->orderBy('tracked_at', $direction);
            $row = $q->first();

            return $row->tracked_at ?? null;
        };

        return $infolist->schema([
            Section::make('Identitas')->columns(3)->schema([
                TextEntry::make('code')->label('Kode')->copyable()->extraAttributes(['class' => 'font-mono']),
                TextEntry::make('customer.name')->label('Pengirim'),
                TextEntry::make('receiver.name')->label('Penerima'),
            ]),

            Section::make('Rute & Moda')->columns(4)->schema([
                TextEntry::make('originCity.name')->label('Asal'),
                TextEntry::make('destinationCity.name')->label('Tujuan'),
                TextEntry::make('route_summary')->label('Ringkas')->columnSpan(2)
                    ->formatStateUsing(fn($state) => $state ?: '—')
                    ->extraAttributes(['class' => 'text-sm text-gray-700']),
                IconEntry::make('mode')->label('Moda')
                    ->icon(fn($state) => ($state?->value ?? $state) === 'sea' ? 'heroicon-o-cog-8-tooth' : 'heroicon-m-truck')
                    ->color(fn($state) => ($state?->value ?? $state) === 'sea' ? 'primary' : 'warning'),
            ]),

            Section::make('Layanan')->columns(4)->schema([
                TextEntry::make('service_type')->label('Jenis')
                    ->formatStateUsing(fn($state) => $state?->label() ?? (string)$state)->badge(),
                TextEntry::make('service_option')->label('Opsi')->badge()
                    ->formatStateUsing(function ($state, $record) {
                        $map = ['fcl' => 'FCL', 'lcl' => 'LCL', 'truck' => 'Truck', 'towing' => 'Towing', 'car_carrier' => 'Car Carrier'];
                        $label = $map[$state] ?? ($state ?: '-');
                        if (($record->mode?->value ?? $record->mode) === 'sea' && $state === 'fcl') {
                            $size = is_string($record->container_size)
                                ? \App\Enums\ContainerSize::tryFrom($record->container_size)?->label()
                                : $record->container_size?->label();
                            $qty  = $record->container_qty ? ' × ' . $record->container_qty : '';
                            if ($size) $label .= " • {$size}{$qty}";
                        }
                        return $label;
                    }),
                TextEntry::make('delivery_scope')->label('Cakupan')
                    ->formatStateUsing(fn($state) => $state?->label() ?? (string)$state)->badge(),
                TextEntry::make('cargo_type')->label('Muatan')
                    ->formatStateUsing(fn($state) => $state?->label() ?? (string)$state)->badge(),
            ]),

            Section::make('Sea Info')->columns(4)
                ->visible(fn($record) => ($record->mode?->value ?? $record->mode) === 'sea')
                ->schema([
                    TextEntry::make('vessel_name')->label('Vessel'),
                    TextEntry::make('voyage')->label('Voyage'),
                    TextEntry::make('pol')->label('POL'),
                    TextEntry::make('pod')->label('POD'),
                    TextEntry::make('etd')->label('ETD')->dateTime('d M Y H:i'),
                    TextEntry::make('eta')->label('ETA')->dateTime('d M Y H:i'),
                ]),

            Section::make('Land Info')->columns(3)
                ->visible(fn($record) => ($record->mode?->value ?? $record->mode) === 'land')
                ->schema([
                    TextEntry::make('armada.code')->label('Armada')->placeholder('—'),
                    TextEntry::make('vehicle_plate')->label('No. Polisi')->placeholder('—'),
                    TextEntry::make('driver.name')->label('Driver')->placeholder('—')
                        ->suffix(fn($record) => $record->driver_phone ? " • {$record->driver_phone}" : null),
                ]),

            // Sea Timeline: Vessel milestones
            Section::make('Timeline Kapal (Sea)')
                ->visible(fn($record) => ($record->mode?->value ?? $record->mode) === 'sea')
                ->columns(4)
                ->schema([
                    TextEntry::make('vessel_depart_at')
                        ->label('ATD (Actual Departure)')
                        ->state(fn($record) => $trackTime($record, ['vessel_depart'], 'asc'))
                        ->dateTime('d M Y H:i')
                        ->placeholder('—'),

                    TextEntry::make('vessel_arrival_at')
                        ->label('ATA (Actual Arrival)')
                        ->state(fn($record) => $trackTime($record, ['vessel_arrival'], 'asc'))
                        ->dateTime('d M Y H:i')
                        ->placeholder('—'),

                    TextEntry::make('loading_at')
                        ->label('Unit Loading')
                        ->state(fn($record) => $trackTime($record, ['unit_loading'], 'asc'))
                        ->dateTime('d M Y H:i')
                        ->placeholder('—'),

                    TextEntry::make('unloading_at')
                        ->label('Unloading')
                        ->state(fn($record) => $trackTime($record, ['unloading'], 'asc'))
                        ->dateTime('d M Y H:i')
                        ->placeholder('—'),
                ]),

            Section::make('Status & Tanggal')->columns(4)->schema([
                TextEntry::make('status')->label('Status Akhir')->badge()
                    ->color(fn($state) => ($state?->label() ?? $state) === 'Terkirim' ? 'success' : 'danger')
                    ->formatStateUsing(fn($state) => $state?->label() ?? (string)$state),

                TextEntry::make('ata_display')
                    ->label('Terkirim (ATA)')
                    ->state(fn($record) => $record->delivered_at ?: $trackTime($record, ['delivered'], 'desc'))
                    ->dateTime('d M Y H:i')
                    ->placeholder('—'),

                TextEntry::make('atd_display')
                    ->label('Berangkat (ATD)')
                    ->state(function ($record) use ($trackTime) {
                        $mode = $record->mode?->value ?? $record->mode;

                        if ($mode === 'sea') {
                            return $trackTime($record, ['vessel_depart', 'onship'], 'asc') ?: ($record->etd ?? null);
                        }

                        return $trackTime($record, ['handover', 'delivery_to_customer', 'unit_loading'], 'asc')
                            ?: ($record->requested_at ?? null);
                    })
                    ->dateTime('d M Y H:i')
                    ->placeholder('—'),

                TextEntry::make('cancelled_at')
                    ->label('Dibatalkan')
                    ->dateTime('d M Y H:i')
                    ->placeholder('—'),

                TextEntry::make('cancelledBy.name')
                    ->label('Dibatalkan Oleh')
                    ->placeholder('—'),
            ]),

            Section::make('Permintaan & Dokumen')->columns(4)->schema([
                TextEntry::make('request_type')->label('Tipe')
                    ->formatStateUsing(fn($state) => $state?->label() ?? (string)$state)->badge(),
                TextEntry::make('doc_number')->label('No. Dok')->placeholder('—'),
                TextEntry::make('priority')->label('Prioritas')
                    ->formatStateUsing(fn($state) => $state ? ucfirst($state) : '—')->badge()
                    ->color(fn($state) => $state === 'urgent' ? 'danger' : 'gray'),
                TextEntry::make('requested_at')->label('Tgl Permintaan')->dateTime('d M Y H:i'),
            ]),

            Section::make('Kuantitas')->columns(3)->schema([
                TextEntry::make('container_summary')->label('Kontainer')
                    ->state(function ($record) {
                        $isSea  = ($record->mode?->value ?? $record->mode) === 'sea';
                        $isFcl  = ($record->service_option ?? null) === 'fcl';
                        $isGen  = ($record->cargo_type?->value ?? $record->cargo_type) === \App\Enums\CargoType::General->value;

                        if (!($isSea && $isFcl && $isGen)) return null;

                        $size = is_string($record->container_size)
                            ? \App\Enums\ContainerSize::tryFrom($record->container_size)?->label()
                            : $record->container_size?->label();

                        if (!$size) return null;
                        $qty = $record->container_qty ? " × {$record->container_qty}" : '';
                        return "{$size}{$qty}";
                    })
                    ->placeholder('—'),

                TextEntry::make('packages_total')->label('Koli')
                    ->visible(
                        fn($record) => ($record->service_option ?? null) === 'lcl' &&
                            ($record->cargo_type?->value ?? $record->cargo_type) === \App\Enums\CargoType::General->value
                    )
                    ->placeholder('—'),

                TextEntry::make('cbm_total')->label('CBM')
                    ->visible(
                        fn($record) => ($record->service_option ?? null) === 'lcl' &&
                            ($record->cargo_type?->value ?? $record->cargo_type) === \App\Enums\CargoType::General->value
                    )
                    ->formatStateUsing(fn($state) => is_null($state) ? '—' : number_format((float)$state, 3, '.', '')),

                TextEntry::make('weight_total')->label('Berat (kg)')
                    ->visible(
                        fn($record) => ($record->service_option ?? null) === 'lcl' &&
                            ($record->cargo_type?->value ?? $record->cargo_type) === \App\Enums\CargoType::General->value
                    )
                    ->formatStateUsing(fn($state) => is_null($state) ? '—' : number_format((float)$state, 2, '.', '')),

                TextEntry::make('units_count')->label('Unit')
                    ->state(fn($record) => method_exists($record, 'units') ? $record->units()->count() : null)
                    ->visible(
                        fn($record) => ($record->cargo_type?->value ?? $record->cargo_type) === \App\Enums\CargoType::Vehicle->value
                    )
                    ->placeholder('—'),
            ]),

            Section::make('Lampiran')->schema([
                ViewEntry::make('attachments_view')
                    ->view('filament.infolists.attachments')
                    ->state(function ($record) {
                        $raw = $record->attachments ?? [];
                        if (is_string($raw)) {
                            $decoded = json_decode($raw, true);
                            $raw = is_array($decoded) ? $decoded : [];
                        }
                        $files = array_values(array_filter((array) $raw));
                        if (empty($files)) {
                            return [];
                        }

                        $disk = Storage::disk('public');

                        return collect($files)->map(function ($path) {
                            $isUrl = str_starts_with($path, 'http://') || str_starts_with($path, 'https://');
                            $name = basename(parse_url($path, PHP_URL_PATH) ?? $path);
                            $ext  = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                            $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']);

                            if ($isUrl) {
                                return ['name' => $name, 'url' => $path, 'exists' => true, 'is_image' => $isImage];
                            }

                            $disk = Storage::disk('public');
                            return [
                                'name' => $name,
                                'url' => $disk->exists($path) ? Storage::url($path) : null,
                                'exists' => $disk->exists($path),
                                'is_image' => $isImage,
                            ];
                        })->all();
                    }),
            ]),
        ]);
    }
}
