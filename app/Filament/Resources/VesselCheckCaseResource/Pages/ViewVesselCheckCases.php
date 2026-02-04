<?php

namespace App\Filament\Resources\VesselCheckCaseResource\Pages;

use App\Filament\Resources\VesselCheckCaseResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\RepeatableEntry;

class ViewVesselCheckCase extends ViewRecord
{
    protected static string $resource = VesselCheckCaseResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Case Summary')
                    ->schema([
                        TextEntry::make('case_status')
                            ->badge()
                            ->formatStateUsing(
                                fn($state) => $state instanceof \App\Enums\VesselCheckStatus
                                    ? $state->value
                                    : (string) $state
                            ),

                        TextEntry::make('delay_flag')
                            ->label('Delay')
                            ->badge()
                            ->formatStateUsing(fn(bool $state) => $state ? 'YES' : 'NO')
                            ->color(fn(bool $state) => $state ? 'danger' : 'gray'),

                        TextEntry::make('opened_at')->dateTime(),
                        TextEntry::make('closed_at')->dateTime(),
                    ]),

                Section::make('Delay Analysis')
                    ->visible(fn($record) => $record->delays->isNotEmpty())
                    ->schema([
                        RepeatableEntry::make('delays')
                            ->schema([
                                TextEntry::make('delay_category'),
                                TextEntry::make('delay_reason'),
                                TextEntry::make('delay_minutes')->label('Delay (minutes)'),
                                TextEntry::make('impact_description'),
                            ]),
                    ]),

                Section::make('Request Improvement')
                    ->visible(fn($record) => $record->requests->isNotEmpty())
                    ->schema([
                        RepeatableEntry::make('requests')
                            ->schema([
                                TextEntry::make('request_type'),
                                TextEntry::make('requested_to'),
                                TextEntry::make('status')->badge(),
                                TextEntry::make('request_note'),
                                TextEntry::make('response_note'),
                            ]),
                    ]),

                Section::make('Alternative Vessel')
                    ->visible(fn($record) => $record->alternatives->isNotEmpty())
                    ->schema([
                        RepeatableEntry::make('alternatives')
                            ->schema([
                                TextEntry::make('vessel.name')->label('Vessel'),
                                TextEntry::make('voyage.voyage_no')->label('Voyage'),
                                TextEntry::make('alt_etd')->dateTime(),
                                TextEntry::make('approval_status')->badge(),
                            ]),
                    ]),

                Section::make('Schedule Revision')
                    ->visible(fn($record) => $record->revisions->isNotEmpty())
                    ->schema([
                        RepeatableEntry::make('revisions')
                            ->schema([
                                TextEntry::make('oldVoyage.voyage_no')->label('Old Voyage'),
                                TextEntry::make('newVoyage.voyage_no')->label('New Voyage'),
                                TextEntry::make('old_etd')->dateTime(),
                                TextEntry::make('new_etd')->dateTime(),
                                TextEntry::make('revision_note'),
                            ]),
                    ]),

            ]);
    }
}
