<?php

namespace App\Filament\Resources\VesselCheckResource\Pages;

use App\Filament\Resources\VesselCheckResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;

class ViewVesselCheck extends ViewRecord
{
    protected static string $resource = VesselCheckResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([

            Section::make('Ringkasan Kesiapan Kapal')
                ->description('Pemeriksaan kesiapan sebelum keberangkatan (D-3 s.d. D-1)')
                ->schema([

                    TextEntry::make('shippingSchedule.voyage.voyage_no')
                        ->label('Voyage')
                        ->weight('bold'),

                    TextEntry::make('check_date')
                        ->label('Tanggal Pemeriksaan')
                        ->date(),

                    TextEntry::make('day_code')
                        ->label('Hari Pemeriksaan')
                        ->badge()
                        ->color(fn($state) => match ($state) {
                            'H-1' => 'danger',
                            'H-2' => 'warning',
                            default => 'gray',
                        }),

                ])
                ->columns(3),

            Section::make('Kondisi Jadwal Keberangkatan (ETD)')
                ->schema([

                    TextEntry::make('etd_plan')
                        ->label('ETD Rencana')
                        ->dateTime(),

                    TextEntry::make('etd_current')
                        ->label('ETD Terakhir')
                        ->dateTime(),

                    TextEntry::make('status_etd')
                        ->label('Status ETD')
                        ->badge()
                        ->getStateUsing(
                            fn($record) =>
                            $record->etd_plan->equalTo($record->etd_current)
                                ? 'Sesuai Jadwal'
                                : 'Terjadi Perubahan'
                        )
                        ->color(
                            fn($record) =>
                            $record->etd_plan->equalTo($record->etd_current)
                                ? 'success'
                                : 'warning'
                        ),
                ])
                ->columns(3),


            Section::make('Hasil Evaluasi')
                ->schema([

                    TextEntry::make('hasil_monitoring')
                        ->label('Kesimpulan Pemeriksaan')
                        ->badge()
                        ->getStateUsing(function ($record) {
                            if ($record->shippingSchedule->vesselCheckCase) {
                                return 'Perlu Tindak Lanjut';
                            }

                            if (! $record->etd_plan->equalTo($record->etd_current)) {
                                return 'Perlu Perhatian';
                            }

                            return 'Aman';
                        })
                        ->color(function ($record) {
                            if ($record->shippingSchedule->vesselCheckCase) {
                                return 'danger';
                            }

                            if (! $record->etd_plan->equalTo($record->etd_current)) {
                                return 'warning';
                            }

                            return 'success';
                        }),

                    TextEntry::make('shippingSchedule.vesselCheckCase.case_status')
                        ->label('Status Tindak Lanjut')
                        ->badge()
                        ->visible(
                            fn($record) =>
                            $record->shippingSchedule->vesselCheckCase !== null
                        )
                        ->formatStateUsing(fn($state) => $state->label())
                        ->color(fn($state) => $state->color()),

                ])
                ->columns(2),


            Section::make('Informasi Tambahan')
                ->schema([

                    TextEntry::make('source')
                        ->label('Sumber Informasi')
                        ->placeholder('—'),

                    TextEntry::make('note')
                        ->label('Catatan')
                        ->placeholder('Tidak ada catatan'),

                ])
                ->columns(2),
        ]);
    }
}
