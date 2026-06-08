<?php

namespace App\Filament\Resources\VesselCheckResource\Pages;

use App\Enums\VesselCheckLogStatus;
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
                ->description('Pemeriksaan kesiapan carrier sebelum keberangkatan (H-2 / H-1)')
                ->schema([

                    TextEntry::make('voyage.voyage_no')
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

            Section::make('Hasil Pemeriksaan')
                ->schema([

                    TextEntry::make('status')
                        ->label('Status')
                        ->badge()
                        ->formatStateUsing(fn($state) => $state instanceof VesselCheckLogStatus
                            ? $state->label()
                            : strtoupper((string) $state))
                        ->color(fn($state) => $state instanceof VesselCheckLogStatus
                            ? $state->color()
                            : 'gray'),

                    TextEntry::make('delay_reason')
                        ->label('Alasan Keterlambatan')
                        ->placeholder('—')
                        ->visible(fn($record) => $record->getRawOriginal('status') === 'late'),

                    TextEntry::make('note')
                        ->label('Catatan')
                        ->placeholder('—')
                        ->columnSpanFull(),

                ])
                ->columns(2),

        ]);
    }
}
