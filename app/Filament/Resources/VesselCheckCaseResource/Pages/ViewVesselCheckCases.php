<?php

namespace App\Filament\Resources\VesselCheckCaseResource\Pages;

use App\Filament\Resources\VesselCheckCaseResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Grid;

class ViewVesselCheckCases extends ViewRecord
{
    protected static string $resource = VesselCheckCaseResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Section::make('Ringkasan Kasus')
                ->schema([
                    Grid::make(2)->schema([
                        TextEntry::make('case_status')
                            ->label('Status Kasus')
                            ->badge()
                            ->formatStateUsing(fn($state) => $state->label())
                            ->color(fn($state) => $state->color()),

                        TextEntry::make('delay_flag')
                            ->label('Perubahan ETD')
                            ->badge()
                            ->formatStateUsing(fn($state) => $state ? 'Ya' : 'Tidak')
                            ->color(fn($state) => $state ? 'danger' : 'success'),

                        TextEntry::make('opened_at')
                            ->label('Mulai Ditangani')
                            ->dateTime(),

                        TextEntry::make('closed_at')
                            ->label('Selesai Ditangani')
                            ->dateTime()
                            ->visible(fn($record) => filled($record->closed_at)),
                    ]),
                ]),

            Section::make('Analisis Keterlambatan')
                ->visible(fn($record) => $record->delays()->exists())
                ->schema([
                    Grid::make(2)->schema([
                        TextEntry::make('delays.delay_category')
                            ->label('Kategori Keterlambatan'),

                        TextEntry::make('delays.delay_minutes')
                            ->label('Durasi Keterlambatan (menit)'),

                        TextEntry::make('delays.delay_reason')
                            ->label('Penyebab Keterlambatan')
                            ->columnSpanFull(),

                        TextEntry::make('delays.impact_description')
                            ->label('Dampak Operasional')
                            ->columnSpanFull(),
                    ]),
                ]),

            Section::make('Permintaan Tindak Lanjut')
                ->visible(fn($record) => $record->requests()->exists())
                ->schema([
                    Grid::make(2)->schema([
                        TextEntry::make('requests.request_type')
                            ->label('Jenis Permintaan'),

                        TextEntry::make('requests.requested_to')
                            ->label('Ditujukan Kepada'),

                        TextEntry::make('requests.status')
                            ->label('Status Permintaan')
                            ->badge(),

                        TextEntry::make('requests.request_note')
                            ->label('Catatan Permintaan')
                            ->columnSpanFull(),

                        TextEntry::make('requests.response_note')
                            ->label('Catatan Tanggapan')
                            ->columnSpanFull()
                            ->visible(fn($record) => filled(optional($record->requests->first())->response_note)),
                    ]),
                ]),

            Section::make('Alternatif Kapal')
                ->visible(fn($record) => $record->alternatives()->exists())
                ->schema([
                    Grid::make(2)->schema([
                        TextEntry::make('alternatives.vessel_name')
                            ->label('Nama Kapal'),

                        TextEntry::make('alternatives.voyage_no')
                            ->label('Voyage'),

                        TextEntry::make('alternatives.alt_etd')
                            ->label('ETD Alternatif')
                            ->dateTime(),

                        TextEntry::make('alternatives.approval_status')
                            ->label('Status Persetujuan')
                            ->badge(),
                    ]),
                ]),

            Section::make('Perubahan Jadwal')
                ->visible(fn($record) => $record->revisions()->exists())
                ->schema([
                    Grid::make(2)->schema([
                        TextEntry::make('revisions.old_voyage')
                            ->label('Voyage Sebelumnya'),

                        TextEntry::make('revisions.new_voyage')
                            ->label('Voyage Baru'),

                        TextEntry::make('revisions.old_etd')
                            ->label('ETD Sebelumnya')
                            ->dateTime(),

                        TextEntry::make('revisions.new_etd')
                            ->label('ETD Terbaru')
                            ->dateTime(),
                    ]),
                ]),
        ]);
    }
}
