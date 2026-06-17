<?php

namespace App\Filament\FC\Pages;

use App\Enums\{ShipmentMode, ServiceType, ContainerSize, DeliveryScope, CargoType};
use App\Models\Shipment;
use Filament\Actions\Action;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\View as ViewComponent;
use Filament\Infolists\Concerns\InteractsWithInfolists;
use Filament\Infolists\Contracts\HasInfolists;
use Filament\Infolists\Infolist;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;

class OperationalShipmentPage extends Page implements HasInfolists
{
    use InteractsWithInfolists;

    protected static bool $shouldRegisterNavigation = false;
    protected static string $view = 'filament.fc.pages.operational-shipment';

    public ?Shipment $record = null;

    public static function getSlug(): string
    {
        return 'operational-shipments';
    }

    public static function getRoutePath(): string
    {
        return 'operational-shipments/{record}';
    }

    public function mount(Shipment $record): void
    {
        $this->record = $record;
        abort_unless(Auth::user()?->can('view', $this->record), 403);
    }

    public function getTitle(): string|Htmlable
    {
        return "Detail Pengiriman · {$this->record->code}";
    }

    public function getBreadcrumbs(): array
    {
        return [
            OperationalTasks::getUrl() => 'Tugas Operasional',
            '#' => $this->getTitle(),
        ];
    }

    protected function makeInfolist(): Infolist
    {
        return parent::makeInfolist()
            ->record($this->record)
            ->columns(2);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('backToOperational')
                ->label('Tugas Operasional')
                ->icon('heroicon-m-arrow-left')
                ->color('gray')
                ->url(fn() => OperationalTasks::getUrl()),

            Action::make('printWaybill')
                ->label('Cetak Waybill')
                ->icon('heroicon-m-printer')
                ->url(fn() => route('shipments.print.waybill', $this->record))
                ->openUrlInNewTab()
                ->color('primary')
                ->visible(fn() => Auth::user()?->can('print', $this->record)),

            Action::make('printPackingList')
                ->label('Cetak Packing List')
                ->icon('heroicon-m-clipboard-document-list')
                ->url(fn() => route('shipments.print.packing', $this->record))
                ->openUrlInNewTab()
                ->color('info')
                ->visible(fn() => Auth::user()?->can('print', $this->record)),

            Action::make('printResi')
                ->label('Cetak Resi')
                ->icon('heroicon-m-document-text')
                ->url(fn() => route('shipments.resi', $this->record))
                ->openUrlInNewTab()
                ->color('gray')
                ->visible(fn() => Auth::user()?->can('print', $this->record)),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Section::make('Ringkasan')
                ->schema([
                    Grid::make(12)->schema([
                        TextEntry::make('code')
                            ->label('Kode')
                            ->badge()
                            ->extraAttributes(['class' => 'font-mono'])
                            ->columnSpan(3),

                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->formatStateUsing(fn($state) => $state?->label() ?? (string) $state ?: '-')
                            ->color(fn($state) => match ($state?->label()) {
                                'Draf' => 'gray',
                                'Menunggu', 'Ditahan' => 'warning',
                                'Penjemputan', 'Dalam Perjalanan' => 'info',
                                'Terkirim' => 'success',
                                'Dibatalkan' => 'danger',
                                default => 'gray',
                            })
                            ->columnSpan(3),

                        TextEntry::make('route_label')
                            ->label('Rute')
                            ->placeholder('—')
                            ->columnSpan(6),

                        TextEntry::make('doc_number')
                            ->label('No Dokumen')
                            ->extraAttributes(['class' => 'font-mono'])
                            ->placeholder('—')
                            ->columnSpan(4),

                        TextEntry::make('request_type')
                            ->label('Tipe')
                            ->formatStateUsing(fn($state) => $state?->label() ?? (string) ($state ?? '—'))
                            ->placeholder('—')
                            ->columnSpan(4),

                        TextEntry::make('requested_at')
                            ->label('Tgl Permintaan')
                            ->date('d M Y')
                            ->placeholder('—')
                            ->columnSpan(4),
                    ]),
                ]),

            Section::make('Pihak Terkait')
                ->schema([
                    Grid::make(12)->schema([
                        TextEntry::make('customer.name')->label('Pengirim')->badge()->placeholder('—')->columnSpan(6),
                        TextEntry::make('receiver.name')->label('Penerima')->badge()->placeholder('—')->columnSpan(6),
                    ]),
                ]),

            Section::make('Kontak Operasional')
                ->schema([
                    Grid::make(12)->schema([
                        TextEntry::make('pic_name')
                            ->label('PIC')
                            ->getStateUsing(function (Shipment $record) {
                                $name  = $record->pic_name
                                    ?? $record->customer?->pic_name
                                    ?? $record->customer?->name;
                                $phone = $record->pic_phone
                                    ?? $record->customer?->pic_phone
                                    ?? $record->customer?->phone;
                                return $name ? ($phone ? "{$name} · {$phone}" : $name) : null;
                            })
                            ->placeholder('—')
                            ->columnSpan(4),

                        TextEntry::make('pickup_contact_name')
                            ->label('Kontak Pickup')
                            ->getStateUsing(function (Shipment $record) {
                                $name  = $record->pickup_contact_name
                                    ?? $record->customer?->pic_name
                                    ?? $record->customer?->name;
                                $phone = $record->pickup_contact_phone
                                    ?? $record->customer?->pic_phone
                                    ?? $record->customer?->phone;
                                return $name ? ($phone ? "{$name} · {$phone}" : $name) : null;
                            })
                            ->placeholder('—')
                            ->columnSpan(4),

                        TextEntry::make('delivery_contact_name')
                            ->label('Kontak Delivery')
                            ->getStateUsing(function (Shipment $record) {
                                $name  = $record->delivery_contact_name
                                    ?? $record->receiver?->pic_name
                                    ?? $record->receiver?->name;
                                $phone = $record->delivery_contact_phone
                                    ?? $record->receiver?->pic_phone
                                    ?? $record->receiver?->phone;
                                return $name ? ($phone ? "{$name} · {$phone}" : $name) : null;
                            })
                            ->placeholder('—')
                            ->columnSpan(4),
                    ]),
                ]),

            Section::make('Layanan & Moda')
                ->schema([
                    Grid::make(12)->schema([
                        TextEntry::make('mode')
                            ->label('Moda')
                            ->formatStateUsing(fn($state) => $state instanceof ShipmentMode ? $state->label() : (string) $state)
                            ->badge()
                            ->color(fn($state) => ($state instanceof ShipmentMode && $state === ShipmentMode::Sea) ? 'primary' : 'warning')
                            ->columnSpan(3),

                        TextEntry::make('service_type')
                            ->label('Layanan')
                            ->formatStateUsing(fn($state) => $state instanceof ServiceType ? $state->label() : (($state !== null ? (string) $state : null) ?: '-'))
                            ->badge()
                            ->columnSpan(3),

                        TextEntry::make('service_option')
                            ->label('Opsi')
                            ->formatStateUsing(fn(?string $state, Shipment $record) => match ($state) {
                                'fcl' => 'FCL' . (function() use ($record) {
                                    $size = $record->container_size instanceof ContainerSize
                                        ? $record->container_size->label()
                                        : \App\Enums\ContainerSize::tryFrom((string) $record->container_size)?->label();
                                    if ($size) {
                                        $qty = $record->container_qty ? " × {$record->container_qty}" : '';
                                        return " • {$size}{$qty}";
                                    }
                                    return '';
                                })(),
                                'lcl' => 'LCL',
                                'truck' => 'Truck',
                                'towing' => 'Towing',
                                'car_carrier' => 'Car Carrier',
                                default => $state ?: '-',
                            })
                            ->badge()
                            ->columnSpan(3),

                        TextEntry::make('delivery_scope')
                            ->label('Cakupan')
                            ->formatStateUsing(fn($state) => $state instanceof DeliveryScope ? $state->label() : (($state !== null ? (string) $state : null) ?: '-'))
                            ->badge()
                            ->columnSpan(3),

                        TextEntry::make('cargo_type')
                            ->label('Muatan')
                            ->formatStateUsing(fn($state) => $state instanceof CargoType ? $state->label() : ((string) $state ?: '-'))
                            ->badge()
                            ->columnSpan(3),
                    ]),
                ]),

            Section::make('Informasi Kapal & Kontainer')
                ->visible(fn(Shipment $record) => $record->mode === ShipmentMode::Sea)
                ->schema([
                    Grid::make(12)->schema([
                        TextEntry::make('vessel_name')->label('Kapal')->placeholder('—')->columnSpan(4),
                        TextEntry::make('voyage')->label('Voyage')->placeholder('—')->columnSpan(2),
                        TextEntry::make('pol')->label('POL')->placeholder('—')->columnSpan(3),
                        TextEntry::make('pod')->label('POD')->placeholder('—')->columnSpan(3),

                        TextEntry::make('container_display')
                            ->label(fn(Shipment $record) => $record->container_count > 1 ? 'Containers' : 'Container')
                            ->getStateUsing(fn(Shipment $record) => $record->container_display ?: null)
                            ->placeholder('—')
                            ->columnSpan(4),

                        TextEntry::make('seal_no')->label('No Segel')->placeholder('—')->columnSpan(4),
                        TextEntry::make('container_qty')->label('Jumlah Kontainer')->placeholder('—')->columnSpan(4),
                    ]),
                ]),

            Section::make('Depo & Muatan')
                ->schema([
                    Grid::make(12)->schema([
                        TextEntry::make('assignedDepot.name')->label('Depo Penugasan')->placeholder('—')->columnSpan(4),

                        TextEntry::make('vehicle_loading')
                            ->label('Tipe Muatan')
                            ->formatStateUsing(fn(?string $state) => match ($state) {
                                'rack' => 'Rack',
                                'flat_rack' => 'Flat Rack',
                                'regular' => 'Reguler',
                                default => $state ?: '—',
                            })
                            ->columnSpan(4),

                        TextEntry::make('priority')
                            ->label('Prioritas')
                            ->badge()
                            ->color(fn(?string $state) => $state === 'urgent' ? 'danger' : 'gray')
                            ->formatStateUsing(fn(?string $state) => match ($state) {
                                'urgent' => 'Urgent',
                                'normal' => 'Normal',
                                default => $state ?: '—',
                            })
                            ->columnSpan(4),
                    ]),
                ]),

            Section::make('Kuantitas & Waktu')
                ->schema([
                    Grid::make(12)->schema([
                        TextEntry::make('packages_total')->label('Koli')->placeholder('—')->columnSpan(3),
                        TextEntry::make('cbm_total')->label('CBM')->formatStateUsing(fn($v) => $v !== null ? number_format((float) $v, 3) : '—')->columnSpan(3),
                        TextEntry::make('weight_total')->label('Berat (kg)')->formatStateUsing(fn($v) => $v !== null ? number_format((float) $v, 2) : '—')->columnSpan(3),
                        TextEntry::make('etd')
                            ->label('ETD')
                            ->getStateUsing(fn (Shipment $record) => $record->etd ?? $record->voyageRecord?->etd)
                            ->dateTime('d M Y H:i')
                            ->placeholder('—')
                            ->columnSpan(3),
                        TextEntry::make('eta')->label('ETA')->dateTime('d M Y H:i')->placeholder('—')->columnSpan(3),
                        TextEntry::make('pickup_date')->label('Tanggal Pickup')->dateTime('d M Y H:i')->placeholder('—')->columnSpan(3),
                        TextEntry::make('estimated_ready_at')->label('Estimasi Selesai')->dateTime('d M Y H:i')->placeholder('—')->columnSpan(3),
                    ]),
                ]),

            Section::make('Daftar Unit & Inspeksi')
                ->schema([
                    ViewComponent::make('daftar_unit')
                        ->view('filament.fc.shipments.partials.daftar-unit'),
                ]),

            Section::make('Catatan')
                ->collapsed()
                ->collapsible()
                ->schema([
                    TextEntry::make('notes')->label('Catatan Pengiriman')->placeholder('—'),
                ]),

            Section::make('Timeline Operasional')
                ->collapsible()
                ->schema([
                    ViewComponent::make('timeline')
                        ->view('filament.fc.shipments.partials.timeline')
                        ->viewData([
                            'items'    => fn() => $this->record
                                ->tracks()
                                ->with(['user:id,name'])
                                ->orderBy('tracked_at', 'asc')
                                ->get(['id', 'shipment_id', 'status', 'tracked_at', 'location', 'note', 'created_by', 'checkseet', 'attachments', 'check_result']),
                            'shipment' => fn() => $this->record,
                        ]),
                ]),
        ]);
    }
}
