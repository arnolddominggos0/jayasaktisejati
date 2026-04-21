<?php

namespace App\Filament\Resources\ShipmentResource\Pages;

use App\Enums\ShipmentMode;
use App\Filament\Resources\ShipmentResource;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions\Action;

class ViewShipment extends ViewRecord
{
    protected static string $resource = ShipmentResource::class;

    protected static ?string $title = 'Detail Shipment';

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Informasi Dasar')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('code')
                            ->label('Kode Shipment'),
                        TextEntry::make('status')
                            ->label('Status')
                            ->badge(),
                        TextEntry::make('mode')
                            ->label('Moda')
                            ->badge(),
                        TextEntry::make('customer.name')
                            ->label('Pengirim'),
                        TextEntry::make('receiver.name')
                            ->label('Penerima'),
                        TextEntry::make('branch.name')
                            ->label('Cabang'),
                    ]),

                Section::make('Loading Operations')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('loadingSessions_count')
                            ->label('Total Loading Session')
                            ->state(fn ($record) => $record->loadingSessions()->count()),
                        TextEntry::make('active_loading_session')
                            ->label('Session Aktif')
                            ->state(function ($record) {
                                $active = $record->loadingSessions()->whereIn('status', ['in_progress', 'draft'])->first();
                                return $active ? $active->code : 'Tidak ada';
                            }),
                    ])
                    ->visible(fn ($record) => $record->loadingSessions()->exists()),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('print_waybill')
                ->label('Cetak Waybill')
                ->icon('heroicon-o-document-text')
                ->color('success')
                ->url(fn ($record) => route('shipments.print.waybill', ['shipment' => $record->id, 'download' => 1]))
                ->openUrlInNewTab()
                ->visible(fn ($record) => $record->mode === ShipmentMode::Sea),

            Action::make('print_packing_list')
                ->label('Cetak Packing List')
                ->icon('heroicon-o-clipboard-document-list')
                ->color('warning')
                ->url(fn ($record) => route('shipments.print.packing', ['shipment' => $record->id, 'download' => 1]))
                ->openUrlInNewTab()
                ->visible(fn ($record) => $record->mode === ShipmentMode::Sea),

            Action::make('create_loading')
                ->label('Buat Loading Session')
                ->icon('heroicon-o-plus')
                ->url(fn ($record) => route('filament.fc.resources.loading-sessions.create', ['shipment_id' => $record->id]))
                ->visible(fn () => auth()->user()?->hasRole('field_coordinator')),

            Action::make('edit')
                ->label('Edit')
                ->url(fn ($record) => $this->getResource()::getUrl('edit', ['record' => $record])),
        ];
    }
}
