<?php

namespace App\Filament\Resources\ShipmentHistoryResource\Pages;

use App\Filament\Resources\ShipmentHistoryResource;
use App\Models\Voyage;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use App\Enums\CargoType;
use Filament\Infolists\Components\{Section, TextEntry, IconEntry, ViewEntry};
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;

class ViewShipmentHistory extends ViewRecord
{
    protected static string $resource = ShipmentHistoryResource::class;

    // ── Voyage relation loader ────────────────────────────────────────────
    // Called in mount() AND in the Livewire hydrate hook so relations survive
    // every re-render, not just the initial page load.
    private function loadVoyageRelations(): void
    {
        if (! $this->record->voyage_id) {
            return;
        }

        // Use load() with a nested constraint — NOT loadMissing(['voyage.xxx']).
        // loadMissing on a dot-path calls Collection::pluck('voyage') internally,
        // which hits getAttribute('voyage') and returns the string snapshot column
        // instead of the Voyage model, causing "relationLoaded() on string".
        // load() with a closure constraint loads the relation and all its children
        // in a single step, bypassing pluck() entirely.
        $this->record->load([
            'voyage' => fn ($q) => $q->with([
                'vessel',
                'delayLogs',
                'milestones',
                'vesselChecks',
                'sailingSla',
            ]),
        ]);
    }

    public function mount(int|string $record): void
    {
        parent::mount($record);
        $this->loadVoyageRelations();
    }

    // Filament/Livewire re-hydrates the model on every request without calling
    // mount() again. Ensure voyage relations are always available.
    public function hydrate(): void
    {
        $this->loadVoyageRelations();
    }

    protected function getHeaderActions(): array
    {
        $record = $this->record;

        return [
            // ── Print documents ───────────────────────────────────────────
            Action::make('print_resi')
                ->label('Cetak Resi')
                ->icon('heroicon-o-document-text')
                ->color('gray')
                ->url(route('shipments.resi', ['shipment' => $record->id]) . '?download=1')
                ->openUrlInNewTab(),

            Action::make('print_waybill')
                ->label('Cetak Waybill')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->url(route('shipments.print.waybill', $record))
                ->openUrlInNewTab(),

            Action::make('print_packing')
                ->label('Packing List')
                ->icon('heroicon-o-clipboard-document-list')
                ->color('gray')
                ->url(route('shipments.print.packing', $record))
                ->openUrlInNewTab(),

            // ── Super Admin ───────────────────────────────────────────────
            // Note: ShipmentTrackingResource scopes out delivered/cancelled records,
            // so "manage" route returns 404 for historical shipments.
            // Tracking timeline is available inline on this page.
            // Super Admin can still edit tracks via "Koreksi Data" → ShipmentResource edit.
            Action::make('correction')
                ->label('Koreksi Data')
                ->icon('heroicon-o-pencil-square')
                ->color('warning')
                ->url(\App\Filament\Resources\ShipmentResource::getUrl('edit', ['record' => $record]))
                ->visible(fn() => auth_user()?->hasRole('super_admin') === true),

            // ── Utility ───────────────────────────────────────────────────
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
        // ── Safe voyage accessor ──────────────────────────────────────────
        // Always use this helper — NEVER use $record->voyage directly.
        // $record->voyage is an ambiguous property: it resolves to either the
        // booking-snapshot string column OR the Voyage Eloquent relation,
        // depending on whether the relation is loaded. Using relationLoaded()
        // first eliminates the collision entirely.
        $voyage = static function ($record): ?Voyage {
            return $record->relationLoaded('voyage')
                ? $record->getRelation('voyage')
                : null;
        };

        // ── Shipment track time helper ────────────────────────────────────
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

            // ── Ringkasan Arsip ───────────────────────────────────────────
            Section::make('Ringkasan Arsip')->columns(4)->schema([
                TextEntry::make('code')
                    ->label('No. Pengiriman')
                    ->copyable()
                    ->badge()
                    ->extraAttributes(['class' => 'font-mono'])
                    ->columnSpan(2),

                TextEntry::make('status')
                    ->label('Status Akhir')
                    ->badge()
                    ->color(fn($state) => ($state?->label() ?? $state) === 'Terkirim' ? 'success' : 'danger')
                    ->formatStateUsing(fn($state) => $state?->label() ?? (string)$state),

                TextEntry::make('completed_at_display')
                    ->label('Tanggal Selesai')
                    ->state(function ($record) use ($trackTime) {
                        if ($record->status?->value === 'delivered') {
                            return $record->delivered_at ?: $trackTime($record, ['delivered'], 'desc');
                        }
                        if ($record->status?->value === 'cancelled') {
                            return $record->cancelled_at ?: $trackTime($record, ['cancelled'], 'desc');
                        }
                        return null;
                    })
                    ->dateTime('d M Y H:i')
                    ->placeholder('—'),

                TextEntry::make('customer.name')->label('Pengirim')->columnSpan(2),
                TextEntry::make('receiver.name')->label('Penerima')->columnSpan(2),

                TextEntry::make('voyage_archive')
                    ->label('Voyage (Booking)')
                    ->state(fn($record) => data_get($record->getAttributes(), 'voyage') ?? '—')
                    ->placeholder('—')
                    ->columnSpan(2),

                TextEntry::make('route_archive')
                    ->label('Rute')
                    ->state(fn($record) =>
                        ($record->originCity?->name ?? '—') . ' → ' . ($record->destinationCity?->name ?? '—')
                    )
                    ->columnSpan(2),
            ]),

            // ── Rute & Moda ───────────────────────────────────────────────
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

            // ── Layanan ───────────────────────────────────────────────────
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

            // ── Sea Info ──────────────────────────────────────────────────
            // NOTE: TextEntry::make('voyage') reads the RAW COLUMN (booking snapshot string).
            // It must use getAttributes() to bypass the Eloquent relation lookup.
            Section::make('Sea Info')->columns(4)
                ->visible(fn($record) => ($record->mode?->value ?? $record->mode) === 'sea')
                ->schema([
                    TextEntry::make('vessel_name')->label('Vessel'),
                    TextEntry::make('voyage')->label('Voyage No (Booking)')
                        ->state(fn($record) => data_get($record->getAttributes(), 'voyage') ?? '—'),
                    TextEntry::make('pol')->label('POL'),
                    TextEntry::make('pod')->label('POD'),
                    TextEntry::make('etd')->label('ETD')->dateTime('d M Y H:i'),
                    TextEntry::make('eta')->label('ETA')->dateTime('d M Y H:i'),
                ]),

            // ── Land Info ─────────────────────────────────────────────────
            Section::make('Land Info')->columns(3)
                ->visible(fn($record) => ($record->mode?->value ?? $record->mode) === 'land')
                ->schema([
                    TextEntry::make('armada.code')->label('Armada')->placeholder('—'),
                    TextEntry::make('vehicle_plate')->label('No. Polisi')->placeholder('—'),
                    TextEntry::make('driver.name')->label('Driver')->placeholder('—')
                        ->suffix(fn($record) => $record->driver_phone ? " • {$record->driver_phone}" : null),
                ]),

            // ── Timeline Kapal (Sea) ──────────────────────────────────────
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

            // ── Informasi Voyage (live dari Voyage model) ─────────────────
            Section::make('Informasi Voyage')
                ->description('Data live dari voyage aktual — mungkin berbeda dari snapshot saat booking.')
                ->visible(fn($record) => $voyage($record) !== null)
                ->columns(4)
                ->schema([
                    TextEntry::make('voy_voyage_no')
                        ->label('Voyage No')
                        ->state(fn($record) => $voyage($record)?->voyage_no ?? '—'),

                    TextEntry::make('voy_vessel')
                        ->label('Kapal (Live)')
                        ->state(fn($record) => $voyage($record)?->vessel?->name ?? '—'),

                    TextEntry::make('voy_etd')
                        ->label('ETD (Live)')
                        ->state(fn($record) => $voyage($record)?->etd?->format('d M Y H:i') ?? '—'),

                    TextEntry::make('voy_eta')
                        ->label('ETA (Live)')
                        ->state(fn($record) => $voyage($record)?->eta?->format('d M Y H:i') ?? '—'),

                    TextEntry::make('voy_atd')
                        ->label('ATD (Aktual)')
                        ->state(fn($record) => $voyage($record)?->atd_at?->format('d M Y H:i') ?? '—'),

                    TextEntry::make('voy_ata')
                        ->label('ATA (Aktual)')
                        ->state(fn($record) => $voyage($record)?->ata_at?->format('d M Y H:i') ?? '—'),

                    TextEntry::make('voy_delay_reason')
                        ->label('Alasan Delay')
                        ->state(fn($record) => $voyage($record)?->manual_delay_reason?->label() ?? '—'),

                    TextEntry::make('voy_cargo_actual')
                        ->label('Cargo Aktual')
                        ->state(function ($record) use ($voyage) {
                            $v = $voyage($record);
                            return $v?->cargo_actual !== null
                                ? number_format($v->cargo_actual) . ' unit'
                                : '—';
                        }),
                ]),

            // ── Voyage Performance (KPI + SLA) ────────────────────────────
            Section::make('Voyage Performance')
                ->description('Hasil KPI dan SLA berdasarkan data aktual voyage.')
                ->visible(fn($record) => $voyage($record) !== null)
                ->columns(4)
                ->schema([
                    TextEntry::make('voy_otb')
                        ->label('OTB (On-Time Berthing)')
                        ->state(fn($record) => match ($voyage($record)?->otb_status?->value) {
                            'ontime' => 'OK',
                            'late'   => 'NG',
                            default  => '—',
                        })
                        ->badge()
                        ->color(fn($state) => match ($state) {
                            'OK'    => 'success',
                            'NG'    => 'danger',
                            default => 'gray',
                        }),

                    TextEntry::make('voy_otd')
                        ->label('OTD (On-Time Departure)')
                        ->state(fn($record) => match ($voyage($record)?->otd_status?->value) {
                            'ontime' => 'OK',
                            'late'   => 'NG',
                            default  => '—',
                        })
                        ->badge()
                        ->color(fn($state) => match ($state) {
                            'OK'    => 'success',
                            'NG'    => 'danger',
                            default => 'gray',
                        }),

                    TextEntry::make('voy_ota')
                        ->label('OTA (On-Time Arrival)')
                        ->state(fn($record) => match ($voyage($record)?->ota_status?->value) {
                            'ontime' => 'OK',
                            'late'   => 'NG',
                            default  => '—',
                        })
                        ->badge()
                        ->color(fn($state) => match ($state) {
                            'OK'    => 'success',
                            'NG'    => 'danger',
                            default => 'gray',
                        }),

                    TextEntry::make('voy_sla')
                        ->label('Voyage SLA')
                        ->state(fn($record) => $voyage($record)?->sla_status?->label() ?? '—')
                        ->badge()
                        ->color(fn($record) => match ($voyage($record)?->sla_status?->value) {
                            'ontime' => 'success',
                            'late'   => 'danger',
                            'risk'   => 'warning',
                            default  => 'gray',
                        }),

                    TextEntry::make('voy_sailing_plan')
                        ->label('Sailing Plan')
                        ->state(function ($record) use ($voyage) {
                            $days = $voyage($record)?->planned_sailing_days;
                            return $days !== null ? $days . ' hari' : '—';
                        }),

                    TextEntry::make('voy_sailing_actual')
                        ->label('Sailing Aktual')
                        ->state(function ($record) use ($voyage) {
                            $days = $voyage($record)?->actual_sailing_days;
                            return $days !== null ? $days . ' hari' : '—';
                        }),

                    TextEntry::make('voy_sla_target')
                        ->label('SLA Target')
                        ->state(function ($record) use ($voyage) {
                            $days = $voyage($record)?->sailingSla?->target_days;
                            return $days !== null ? $days . ' hari' : '—';
                        }),

                    TextEntry::make('voy_departure_delay')
                        ->label('Delay Keberangkatan')
                        ->state(function ($record) use ($voyage) {
                            $days = $voyage($record)?->departure_delay_days;
                            if ($days === null) return '—';
                            return $days > 0 ? '+' . $days . ' hari' : 'Tepat Waktu';
                        })
                        ->color(fn($record) => (($voyage($record)?->departure_delay_days) ?? 0) > 0
                            ? 'danger'
                            : 'success'),
                ]),

            // ── Carrier Readiness Snapshot ────────────────────────────────
            Section::make('Carrier Readiness')
                ->description('Hasil pemeriksaan kesiapan vessel sebelum keberangkatan.')
                ->visible(fn($record) => ($voyage($record)?->vesselChecks?->isNotEmpty()) === true)
                ->schema([
                    ViewEntry::make('carrier_readiness')
                        ->view('filament.infolists.voyage-readiness-snapshot')
                        ->state(fn($record) => $voyage($record)?->vesselChecks ?? collect())
                        ->columnSpanFull(),
                ]),

            // ── Voyage Milestones ─────────────────────────────────────────
            Section::make('Voyage Milestones')
                ->description('Progress milestone pelayaran (D+2 hingga D+12).')
                ->visible(fn($record) => ($voyage($record)?->milestones?->isNotEmpty()) === true)
                ->schema([
                    ViewEntry::make('voyage_milestones')
                        ->view('filament.infolists.voyage-milestone-progress')
                        ->state(fn($record) => $voyage($record)?->milestones
                            ?->sortBy(fn($m) => (int) str_replace('d', '', $m->code ?? '0'))
                            ?? collect())
                        ->columnSpanFull(),
                ]),

            // ── Voyage Delay History ──────────────────────────────────────
            Section::make('Voyage Delay History')
                ->description('Riwayat perubahan jadwal ETD/ETA.')
                ->visible(fn($record) => ($voyage($record)?->delayLogs?->isNotEmpty()) === true)
                ->schema([
                    ViewEntry::make('voyage_delay_history')
                        ->view('filament.infolists.voyage-delay-history')
                        ->state(fn($record) => $voyage($record)?->delayLogs
                            ?->sortByDesc('created_at')
                            ?? collect())
                        ->columnSpanFull(),
                ]),

            // ── Lead Time Breakdown ───────────────────────────────────────
            Section::make('Lead Time Breakdown')
                ->description('Breakdown waktu berdasarkan milestone shipment (Dwelling → Sailing → Dooring).')
                ->visible(fn($record) => ($record->mode?->value ?? $record->mode) === 'sea'
                    && $voyage($record) !== null)
                ->columns(4)
                ->schema([
                    TextEntry::make('lt_dwelling')
                        ->label('Dwelling')
                        ->state(fn($record) => $record->dwelling_days !== null
                            ? $record->dwelling_days . ' hari'
                            : '—')
                        ->tooltip('Pickup → Onboard'),

                    TextEntry::make('lt_sailing')
                        ->label('Sailing')
                        ->state(fn($record) => $record->sailing_days !== null
                            ? $record->sailing_days . ' hari'
                            : '—')
                        ->tooltip('Onboard → Arrived'),

                    TextEntry::make('lt_dooring')
                        ->label('Dooring')
                        ->state(fn($record) => $record->dooring_days !== null
                            ? $record->dooring_days . ' hari'
                            : '—')
                        ->tooltip('Arrived → Delivered'),

                    TextEntry::make('lt_total')
                        ->label('Total Lead Time')
                        ->state(fn($record) => $record->lead_time_days !== null
                            ? $record->lead_time_days . ' hari'
                            : '—'),
                ]),

            // ── Status & Tanggal ──────────────────────────────────────────
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

            // ── Permintaan & Dokumen ──────────────────────────────────────
            Section::make('Permintaan & Dokumen')->columns(4)->schema([
                TextEntry::make('request_type')->label('Tipe')
                    ->formatStateUsing(fn($state) => $state?->label() ?? (string)$state)->badge(),
                TextEntry::make('doc_number')->label('No. Dok')->placeholder('—'),
                TextEntry::make('priority')->label('Prioritas')
                    ->formatStateUsing(fn($state) => $state ? ucfirst($state) : '—')->badge()
                    ->color(fn($state) => $state === 'urgent' ? 'danger' : 'gray'),
                TextEntry::make('requested_at')->label('Tgl Permintaan')->dateTime('d M Y H:i'),
            ]),

            // ── Kuantitas ─────────────────────────────────────────────────
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

            // ── Unit Kendaraan ────────────────────────────────────────────
            Section::make('Unit Kendaraan')
                ->description('Daftar unit yang dikirim dalam SPPB ini. Read-only arsip.')
                ->icon('heroicon-m-truck')
                ->visible(fn($record) =>
                    ($record->cargo_type?->value ?? $record->cargo_type) === CargoType::Vehicle->value
                    && $record->units()->exists()
                )
                ->schema([
                    ViewEntry::make('vehicle_units')
                        ->label('')
                        ->view('filament.infolists.history-units')
                        ->state(fn($record) => $record->units()->orderBy('id')->get())
                        ->columnSpanFull(),
                ]),

            // ── Tracking Timeline ─────────────────────────────────────────
            Section::make('Tracking Timeline')
                ->description('Kronologi status pengiriman dari awal hingga selesai.')
                ->icon('heroicon-m-map-pin')
                ->collapsible()
                ->collapsed(false)
                ->schema([
                    ViewEntry::make('tracking_events')
                        ->label('')
                        ->view('filament.infolists.history-timeline')
                        ->state(fn($record) => $record->tracks()->orderBy('tracked_at')->get())
                        ->columnSpanFull(),
                ]),

            // ── Lampiran ──────────────────────────────────────────────────
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
