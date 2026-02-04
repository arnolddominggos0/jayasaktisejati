<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VesselCheckResource\Pages;
use App\Models\VesselCheck;
use App\Services\VesselCheckService;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

class VesselCheckResource extends Resource
{
    protected static ?string $model = VesselCheck::class;

    protected static ?string $navigationLabel = 'Vessel Check (Daily)';
    protected static ?string $navigationGroup = 'Monitoring Kapal TAM';
    protected static ?string $navigationIcon  = 'heroicon-o-calendar-days';
    protected static ?int $navigationSort = 2;

    public static function table(Table $table): Table
    {
        return $table
            ->filters([
                Filter::make('today')
                    ->label('Hari Ini')
                    ->query(
                        fn(Builder $query) =>
                        $query->whereDate('check_date', today())
                    )
                    ->default(),
            ])

            ->columns([
                TextColumn::make('check_date')
                    ->label('Tanggal')
                    ->date(),

                TextColumn::make('day_code')
                    ->label('D')
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'D-1' => 'danger',
                        'D-2' => 'warning',
                        default => 'gray',
                    }),

                TextColumn::make('shippingSchedule.voyage.voyage_no')
                    ->label('Voyage')
                    ->searchable(),

                TextColumn::make('etd_plan')
                    ->label('ETD Plan')
                    ->dateTime(),

                TextColumn::make('etd_current')
                    ->label('ETD Current')
                    ->dateTime(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(
                        fn($state) =>
                        $state->value === 'on_schedule'
                            ? 'Aman'
                            : 'Waspada'
                    )
                    ->color(
                        fn($state) =>
                        $state->value === 'on_schedule'
                            ? 'success'
                            : 'warning'
                    ),

                TextColumn::make('shippingSchedule.vesselCheckCase.case_status')
                    ->label('Kasus')
                    ->badge()
                    ->formatStateUsing(
                        fn($state) =>
                        $state ? $state->label() : '—'
                    )
                    ->color(
                        fn($state) =>
                        $state ? $state->color() : 'gray'
                    ),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),

                Tables\Actions\Action::make('open_issue')
                    ->label('Buka Kasus')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->color('danger')
                    ->visible(
                        fn($record) =>
                        $record->status->value === 'potential_delay'
                            && ! $record->shippingSchedule->vesselCheckCase
                    )
                    ->requiresConfirmation()
                    ->action(
                        fn($record) =>
                        app(VesselCheckService::class)
                            ->openIssueFromCheck($record->id)
                    ),
            ])

            ->defaultSort('check_date', 'asc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVesselChecks::route('/'),
            'view'  => Pages\ViewVesselCheck::route('/{record}'),
        ];
    }
}
