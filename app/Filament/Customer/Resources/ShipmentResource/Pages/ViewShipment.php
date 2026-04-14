<?php

namespace App\Filament\Customer\Resources\ShipmentResource\Pages;

use App\Filament\Customer\Resources\ShipmentResource;
use App\Models\Shipment;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions;

/**
 * View Shipment Page
 * 
 * Display detailed shipment information with tracking history
 */
class ViewShipment extends ViewRecord
{
    protected static string $resource = ShipmentResource::class;

    protected static ?string $title = 'Detail Pengiriman';

    /**
     * Get header actions
     */
    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('downloadResi')
                ->label('Download Resi')
                ->icon('heroicon-o-document-arrow-down')
                ->color('primary')
                ->url(fn () => route('shipments.print.waybill', $this->record))
                ->openUrlInNewTab()
                ->visible(fn () => true), // Add condition if needed

            Actions\Action::make('track')
                ->label('Lacak')
                ->icon('heroicon-o-map-pin')
                ->color('success')
                ->url(fn () => "https://tracking.example.com/{$this->record->code}")
                ->openUrlInNewTab()
                ->visible(false), // Disable external tracking for now
        ];
    }

    /**
     * Configure infolist for viewing shipment details
     */
    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                // Status Section
                Infolists\Components\Section::make('Status Pengiriman')
                    ->schema([
                        Infolists\Components\TextEntry::make('status')
                            ->label('Status Terkini')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'Draft' => 'gray',
                                'Pickup' => 'info',
                                'Transit' => 'warning',
                                'Delivered' => 'success',
                                'Hold' => 'danger',
                                'Cancelled' => 'danger',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'Draft' => 'Draft',
                                'Pickup' => 'Pickup',
                                'Transit' => 'Dalam Perjalanan',
                                'Delivered' => 'Terkirim',
                                'Hold' => 'Tertahan',
                                'Cancelled' => 'Dibatalkan',
                                default => $state,
                            })
                            ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                            ->weight('font-bold'),

                        Infolists\Components\TextEntry::make('latestTrack.status')
                            ->label('Update Terakhir')
                            ->placeholder('Belum ada update'),
                    ])
                    ->columns(2),

                // Shipment Info Section
                Infolists\Components\Section::make('Informasi Pengiriman')
                    ->schema([
                        Infolists\Components\TextEntry::make('code')
                            ->label('No. Resi')
                            ->copyable()
                            ->icon('heroicon-o-clipboard-document'),

                        Infolists\Components\TextEntry::make('service_type')
                            ->label('Jenis Layanan')
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'SeaFreight' => 'Sea Freight',
                                'LandTrucking' => 'Land Trucking',
                                'CarCarrier' => 'Car Carrier',
                                default => $state,
                            }),

                        Infolists\Components\TextEntry::make('mode')
                            ->label('Mode')
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'Sea' => 'Laut',
                                'Land' => 'Darat',
                                default => $state,
                            }),

                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Tanggal Dibuat')
                            ->dateTime('d M Y H:i'),
                    ])
                    ->columns(2),

                // Parties Section
                Infolists\Components\Section::make('Pihak Terkait')
                    ->schema([
                        Infolists\Components\Fieldset::make('Pengirim')
                            ->schema([
                                Infolists\Components\TextEntry::make('customer.name')
                                    ->label('Nama'),
                                Infolists\Components\TextEntry::make('customer.address')
                                    ->label('Alamat')
                                    ->placeholder('-'),
                            ]),

                        Infolists\Components\Fieldset::make('Penerima')
                            ->schema([
                                Infolists\Components\TextEntry::make('receiver.name')
                                    ->label('Nama'),
                                Infolists\Components\TextEntry::make('receiver.address')
                                    ->label('Alamat')
                                    ->placeholder('-'),
                                Infolists\Components\TextEntry::make('receiver.pic_phone')
                                    ->label('Telepon')
                                    ->placeholder('-'),
                            ]),
                    ]),

                // Route Section
                Infolists\Components\Section::make('Rute Pengiriman')
                    ->schema([
                        Infolists\Components\TextEntry::make('origin_city')
                            ->label('Kota Asal')
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('destination_city')
                            ->label('Kota Tujuan')
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('pol')
                            ->label('Pelabuhan Muat (POL)')
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('pod')
                            ->label('Pelabuhan Bongkar (POD)')
                            ->placeholder('-'),
                    ])
                    ->columns(2),

                // Schedule Section
                Infolists\Components\Section::make('Jadwal')
                    ->schema([
                        Infolists\Components\TextEntry::make('pickup_date')
                            ->label('Tanggal Pickup')
                            ->dateTime('d M Y')
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('eta')
                            ->label('Estimasi Sampai (ETA)')
                            ->dateTime('d M Y')
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('delivered_at')
                            ->label('Tanggal Terkirim')
                            ->dateTime('d M Y H:i')
                            ->placeholder('Belum terkirim')
                            ->visible(fn (Shipment $record) => $record->delivered_at !== null),
                    ])
                    ->columns(2),

                // Cargo Section
                Infolists\Components\Section::make('Detail Barang')
                    ->schema([
                        Infolists\Components\TextEntry::make('total_colli')
                            ->label('Total Colli')
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('weight_total')
                            ->label('Total Berat (kg)')
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('cbm_total')
                            ->label('Total Volume (CBM)')
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('cargo_type')
                            ->label('Jenis Barang')
                            ->placeholder('-'),
                    ])
                    ->columns(2),

                // Voyage Section (if sea shipment)
                Infolists\Components\Section::make('Informasi Kapal')
                    ->schema([
                        Infolists\Components\TextEntry::make('voyage.voyage_number')
                            ->label('Nomor Voyage')
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('voyage.vessel.name')
                            ->label('Nama Kapal')
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('voyage.etd')
                            ->label('ETD')
                            ->dateTime('d M Y')
                            ->placeholder('-'),

                        Infolists\Components\TextEntry::make('voyage.eta')
                            ->label('ETA')
                            ->dateTime('d M Y')
                            ->placeholder('-'),
                    ])
                    ->columns(2)
                    ->visible(fn (Shipment $record) => $record->mode === 'Sea' || $record->voyage_id !== null),

                // Tracking History Section
                Infolists\Components\Section::make('Riwayat Tracking')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('tracks')
                            ->hiddenLabel()
                            ->schema([
                                Infolists\Components\Grid::make(3)
                                    ->schema([
                                        Infolists\Components\TextEntry::make('status')
                                            ->label('Status')
                                            ->badge(),

                                        Infolists\Components\TextEntry::make('location')
                                            ->label('Lokasi')
                                            ->placeholder('-'),

                                        Infolists\Components\TextEntry::make('occurred_at')
                                            ->label('Waktu')
                                            ->dateTime('d M Y H:i'),
                                    ]),

                                Infolists\Components\TextEntry::make('notes')
                                    ->label('Catatan')
                                    ->placeholder('-')
                                    ->columnSpanFull(),
                            ])
                            ->contained(false)
                            ->emptyStateMessage('Belum ada riwayat tracking'),
                    ]),

                // Notes Section
                Infolists\Components\Section::make('Catatan')
                    ->schema([
                        Infolists\Components\TextEntry::make('notes')
                            ->hiddenLabel()
                            ->placeholder('Tidak ada catatan'),
                    ])
                    ->visible(fn (Shipment $record) => !empty($record->notes)),
            ]);
    }
}
