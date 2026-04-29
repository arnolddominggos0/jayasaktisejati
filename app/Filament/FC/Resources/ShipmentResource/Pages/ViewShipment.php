<?php

namespace App\Filament\FC\Resources\ShipmentResource\Pages;

use App\Enums\{ShipmentMode, ServiceType, ContainerSize, DeliveryScope, CargoType};
use App\Filament\FC\Resources\ShipmentResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\View as ViewComponent; 

class ViewShipment extends ViewRecord
{
    protected static string $resource = ShipmentResource::class;

    public function getTitle(): string
    {
        /** @var Shipment $record */
        $record = $this->record;
        return "Detail Pengiriman · {$record->code}";
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
                            ->columnSpan(6),
                    ]),
                ]),

            Section::make('Pihak Terkait')
                ->schema([
                    Grid::make(12)->schema([
                        TextEntry::make('customer.name')->label('Pengirim')->badge()->columnSpan(6),
                        TextEntry::make('receiver.name')->label('Penerima')->badge()->columnSpan(6),
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
                                'fcl' => 'FCL' . (function () use ($record) {
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

            Section::make('Kuantitas & Waktu')
                ->schema([
                    Grid::make(12)->schema([
                        TextEntry::make('packages_total')->label('Koli')->placeholder('—')->columnSpan(3),
                        TextEntry::make('cbm_total')->label('CBM')->formatStateUsing(fn($v) => $v !== null ? number_format((float)$v, 3) : '—')->columnSpan(3),
                        TextEntry::make('weight_total')->label('Berat (kg)')->formatStateUsing(fn($v) => $v !== null ? number_format((float)$v, 2) : '—')->columnSpan(3),

                        TextEntry::make('etd')->label('ETD')->dateTime('d M Y H:i')->placeholder('—')->columnSpan(3),
                        TextEntry::make('eta')->label('ETA')->dateTime('d M Y H:i')->placeholder('—')->columnSpan(3),
                        TextEntry::make('pickup_date')->label('Tanggal Pickup')->dateTime('d M Y H:i')->placeholder('—')->columnSpan(3),
                        TextEntry::make('estimated_ready_at')->label('Estimasi Selesai')->dateTime('d M Y H:i')->placeholder('—')->columnSpan(3),
                    ]),
                ]),

            Section::make('Catatan')
                ->collapsed()
                ->collapsible()
                ->schema([
                    TextEntry::make('notes')->label('Catatan Pengiriman')->placeholder('—'),
                ]),

            Section::make('Timeline Status')
                ->collapsible()
                ->schema([
                    ViewComponent::make('timeline')
                        ->view('filament.fc.shipments.partials.timeline')
                        ->viewData([
                            'items' => fn() => $this->record
                                ->tracks()
                                ->with(['user:id,name'])
                                ->orderByDesc('tracked_at')
                                ->get(['id', 'shipment_id', 'status', 'tracked_at', 'location', 'note', 'created_by']),
                        ]),
                ]),

        ]);
    }
}
