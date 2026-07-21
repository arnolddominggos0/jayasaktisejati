<?php

namespace App\Filament\Resources\ShipmentResource\Pages;

use App\Enums\ShipmentMode;
use App\Enums\ShipmentStatus;
use App\Filament\Resources\ShipmentResource;
use App\Filament\Resources\UnitResource;
use App\Models\UnitInspection;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Illuminate\Support\Facades\DB;

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
                            ->label('Nomor Resi'),
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
                        TextEntry::make('originCity.name')
                            ->label('Kota Asal')
                            ->placeholder('—'),
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

                // ── Ringkasan Pemeriksaan Checksheet ──────────────────────────
                Section::make('Ringkasan Pemeriksaan')
                    ->icon('heroicon-m-clipboard-document-check')
                    ->columns(4)
                    ->schema([
                        TextEntry::make('insp_total_unit')
                            ->label('Unit')
                            ->getStateUsing(fn ($record): int =>
                                (int) DB::table('units')
                                    ->where('shipment_id', $record->id)
                                    ->count()
                            )
                            ->weight('bold'),

                        TextEntry::make('insp_sudah')
                            ->label('Sudah Diperiksa')
                            ->getStateUsing(fn ($record): int =>
                                (int) DB::table('units as u')
                                    ->join('unit_inspections as ui', 'ui.unit_id', '=', 'u.id')
                                    ->where('u.shipment_id', $record->id)
                                    ->distinct('u.id')
                                    ->count('u.id')
                            )
                            ->badge()
                            ->color('success'),

                        TextEntry::make('insp_belum')
                            ->label('Belum Diperiksa')
                            ->getStateUsing(function ($record): int {
                                $total = (int) DB::table('units')
                                    ->where('shipment_id', $record->id)
                                    ->count();
                                $sudah = (int) DB::table('units as u')
                                    ->join('unit_inspections as ui', 'ui.unit_id', '=', 'u.id')
                                    ->where('u.shipment_id', $record->id)
                                    ->distinct('u.id')
                                    ->count('u.id');
                                return max(0, $total - $sudah);
                            })
                            ->badge()
                            ->color(fn (int $state) => $state > 0 ? 'warning' : 'gray'),

                        TextEntry::make('insp_ng')
                            ->label('Temuan NG')
                            ->getStateUsing(fn ($record): int =>
                                (int) DB::table('unit_inspection_items as uii')
                                    ->join('unit_inspections as ui', 'ui.id', '=', 'uii.unit_inspection_id')
                                    ->join('units as u', 'u.id', '=', 'ui.unit_id')
                                    ->where('u.shipment_id', $record->id)
                                    ->where('uii.result', 'ng')
                                    ->count()
                            )
                            ->badge()
                            ->color(fn (int $state) => $state > 0 ? 'danger' : 'gray'),

                        TextEntry::make('insp_link')
                            ->label('')
                            ->getStateUsing(fn ($record): string => 'Lihat Unit →')
                            ->url(fn ($record): string =>
                                UnitResource::getUrl('index') . '?tableFilters[shipment_id][value]=' . $record->id
                            )
                            ->color('primary')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('print_resi')
                ->label('Cetak Resi')
                ->icon('heroicon-o-document-text')
                ->color('primary')
                ->url(fn ($record) => route('shipments.resi', ['shipment' => $record->id]) . '?download=1')
                ->openUrlInNewTab()
                ->visible(fn ($record) => $record->status !== ShipmentStatus::Draft),

            ActionGroup::make([
                Action::make('print_waybill')
                    ->label('Cetak Waybill')
                    ->icon('heroicon-o-printer')
                    ->url(fn ($record) => route('shipments.print.waybill', ['shipment' => $record->id, 'download' => 1]))
                    ->openUrlInNewTab()
                    ->visible(fn ($record) => $record->mode === ShipmentMode::Sea),

                Action::make('print_packing_list')
                    ->label('Cetak Packing List')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->url(fn ($record) => route('shipments.print.packing', ['shipment' => $record->id, 'download' => 1]))
                    ->openUrlInNewTab()
                    ->visible(fn ($record) => $record->mode === ShipmentMode::Sea),
            ])
                ->label('Dokumen Lain')
                ->icon('heroicon-o-ellipsis-vertical')
                ->color('gray'),

            Action::make('create_loading')
                ->label('Buat Loading Session')
                ->icon('heroicon-o-plus')
                ->url(fn ($record) => route('filament.fc.resources.loading-sessions.create', ['shipment_id' => $record->id]))
                ->visible(fn () => auth()->user()?->isFieldCoordinator()),

            Action::make('edit')
                ->label('Edit')
                ->url(fn ($record) => $this->getResource()::getUrl('edit', ['record' => $record])),
        ];
    }
}
