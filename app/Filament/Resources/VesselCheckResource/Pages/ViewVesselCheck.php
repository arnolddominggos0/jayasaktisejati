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
            Section::make('Vessel Check (Daily)')
                ->schema([
                    TextEntry::make('check_date')->date(),
                    TextEntry::make('day_code'),
                    TextEntry::make('etd_plan')->dateTime(),
                    TextEntry::make('etd_current')->dateTime(),

                    TextEntry::make('status')
                        ->badge()
                        ->formatStateUsing(fn ($state) =>
                            $state->value === 'on_schedule'
                                ? 'Aman'
                                : 'Waspada'
                        )
                        ->color(fn ($state) =>
                            $state->value === 'on_schedule'
                                ? 'success'
                                : 'warning'
                        ),

                    TextEntry::make('shippingSchedule.vesselCheckCase.case_status')
                        ->label('Status Kasus')
                        ->badge()
                        ->visible(fn ($record) =>
                            $record->shippingSchedule->vesselCheckCase !== null
                        )
                        ->formatStateUsing(fn ($state) => $state->label())
                        ->color(fn ($state) => $state->color()),

                    TextEntry::make('source'),
                    TextEntry::make('note'),
                ]),
        ]);
    }
}
