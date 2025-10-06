<?php

namespace App\Filament\Resources\ShipmentHistoryResource\Pages;

use App\Filament\Resources\ShipmentHistoryResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\{Section, TextEntry, IconEntry};
use Illuminate\Support\Facades\Storage;

class ViewShipmentHistory extends ViewRecord
{
    protected static string $resource = ShipmentHistoryResource::class;

    protected function getHeaderActions(): array
    {
        $record = $this->record;

        return [
            \Filament\Actions\Action::make('print_resi')
                ->label('Cetak Resi')
                ->icon('heroicon-o-printer')
                ->url(route('shipments.resi', ['shipment' => $record->id]) . '?download=1')
                ->openUrlInNewTab(),

            \Filament\Actions\Action::make('timeline')
                ->label('Kelola Timeline')
                ->icon('heroicon-o-sparkles')
                ->color('primary')
                ->url(\App\Filament\Resources\ShipmentTrackingResource::getUrl('manage', ['record' => $record]))
                ->visible(fn() => auth_user()?->hasRole('super_admin') === true),

            \Filament\Actions\Action::make('edit')
                ->label('Edit')
                ->icon('heroicon-o-pencil-square')
                ->color('warning')
                ->url(\App\Filament\Resources\ShipmentResource::getUrl('edit', ['record' => $record]))
                ->visible(fn() => auth_user()?->hasRole('super_admin') === true),

            \Filament\Actions\Action::make('copy_link')
                ->label('Salin Link')
                ->icon('heroicon-o-link')
                ->action(fn() => \Filament\Support\Facades\FilamentView::panel('admin')->js('navigator.clipboard.writeText(window.location.href)'))
                ->color('gray'),
        ];
    }


    public function infolist(Infolist $infolist): Infolist
    {
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

            Section::make('Status & Tanggal')->columns(4)->schema([
                TextEntry::make('status')->label('Status Akhir')->badge()
                    ->color(fn($state) => ($state?->label() ?? $state) === 'Terkirim' ? 'success' : 'danger')
                    ->formatStateUsing(fn($state) => $state?->label() ?? (string)$state),
                TextEntry::make('delivered_at')->label('Terkirim')->dateTime('d M Y H:i')->placeholder('—'),
                TextEntry::make('cancelled_at')->label('Dibatalkan')->dateTime('d M Y H:i')->placeholder('—'),
                TextEntry::make('cancelledBy.name')->label('Dibatalkan Oleh')->placeholder('—'),
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
                TextEntry::make('packages_total')->label('Koli')->placeholder('—'),
                TextEntry::make('cbm_total')->label('CBM')
                    ->formatStateUsing(fn($value) => is_null($value) ? '—' : number_format((float)$value, 3, '.', '')),
                TextEntry::make('weight_total')->label('Berat (kg)')
                    ->formatStateUsing(fn($value) => is_null($value) ? '—' : number_format((float)$value, 2, '.', '')),
            ]),

            TextEntry::make('attachments')->label('Lampiran')
                ->formatStateUsing(
                    fn($state) => empty($state)
                        ? '—'
                        : collect($state)->map(fn($p) => '<a href="' . Storage::url($p) . '" target="_blank">' . $p . '</a>')->join('<br>')
                )
                ->html(),
        ]);
    }
}
