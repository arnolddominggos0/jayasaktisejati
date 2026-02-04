<?php

namespace App\Filament\Resources;

use App\Enums\VesselCheckStatus;
use App\Filament\Resources\VesselCheckCaseResource\Pages;
use App\Models\VesselCheckCase;
use Filament\Forms\Form;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class VesselCheckCaseResource extends Resource
{
    protected static ?string $model = VesselCheckCase::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationGroup = 'Monitoring Kapal TAM';
    protected static ?string $pluralModelLabel = 'Kasus Pemeriksaan Kapal';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('Case ID')
                    ->sortable(),

                TextColumn::make('shippingSchedule.voyage.voyage_no')
                    ->label('Voyage')
                    ->searchable(),

                TextColumn::make('shippingSchedule.voyage.etd')
                    ->label('ETD')
                    ->dateTime(),

                TextColumn::make('case_status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn(VesselCheckStatus $state) => $state->value)
                    ->color(fn(VesselCheckStatus $state) => match ($state) {
                        VesselCheckStatus::ETD_DELAY   => 'warning',
                        VesselCheckStatus::IN_PROGRESS => 'info',
                        VesselCheckStatus::RESOLVED    => 'success',
                        VesselCheckStatus::COMPLETED   => 'success',
                    }),
                TextColumn::make('delay_flag')
                    ->label('Delay')
                    ->badge()
                    ->formatStateUsing(fn($state) => $state ? 'YES' : 'NO')
                    ->color(fn($state) => $state ? 'danger' : 'gray'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->defaultSort('opened_at', 'desc');
    }
    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVesselCheckCases::route('/'),
            'view'  => Pages\ViewVesselCheckCase::route('/{record}'),
        ];
    }
}
