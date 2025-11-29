<?php

namespace App\Filament\Resources;

use App\Actions\Schedule\CreateFromVoyage;
use App\Enums\ScheduleState;
use App\Filament\Resources\VoyageResource\Pages;
use App\Filament\Resources\VoyageResource\RelationManagers\ScheduleRelationManager;
use App\Models\Port;
use App\Models\Voyage;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class VoyageResource extends Resource
{
    protected static ?string $model = Voyage::class;
    protected static ?string $navigationGroup = 'Operasional Kapal';
    protected static ?string $navigationIcon = 'heroicon-o-paper-airplane';
    protected static ?string $navigationLabel = 'Voyage';

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('vessel_id')
                ->relationship('vessel', 'name')
                ->required()
                ->searchable()
                ->preload()
                ->label('Kapal'),

            Select::make('pol_id')
                ->relationship('pol', 'code')
                ->required()
                ->searchable()
                ->preload()
                ->getSearchResultsUsing(fn(string $search) => Port::query()
                    ->when($search !== '', fn($q) => $q
                        ->where('code', 'ilike', "%{$search}%")
                        ->orWhere('name', 'ilike', "%{$search}%"))
                    ->limit(50)
                    ->pluck('code', 'id'))
                ->getOptionLabelUsing(fn($value) => optional(Port::find($value))
                    ?->code . ' — ' . optional(Port::find($value))?->name)
                ->label('POL'),

            Select::make('pod_id')
                ->relationship('pod', 'code')
                ->required()
                ->searchable()
                ->preload()
                ->getSearchResultsUsing(fn(string $search) => Port::query()
                    ->when($search !== '', fn($q) => $q
                        ->where('code', 'ilike', "%{$search}%")
                        ->orWhere('name', 'ilike', "%{$search}%"))
                    ->limit(50)
                    ->pluck('code', 'id'))
                ->getOptionLabelUsing(fn($value) => optional(Port::find($value))
                    ?->code . ' — ' . optional(Port::find($value))?->name)
                ->label('POD'),

            TextInput::make('voyage_no')
                ->required()
                ->maxLength(50)
                ->label('Voyage No'),

            TextInput::make('service')
                ->maxLength(50)
                ->label('Service'),

            DateTimePicker::make('etd')
                ->required()
                ->label('ETD'),

            DateTimePicker::make('eta')
                ->required()
                ->label('ETA'),

            DateTimePicker::make('atd_at')
                ->label('ATD (Actual)'),

            DateTimePicker::make('ata_at')
                ->label('ATA (Actual)'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('vessel.shippingLine.name')
                    ->label('Pelayaran')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('vessel.name')
                    ->label('Kapal')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('voyage_no')
                    ->label('Voyage')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('pol.code')
                    ->label('POL')
                    ->sortable(),

                TextColumn::make('pod.code')
                    ->label('POD')
                    ->sortable(),

                TextColumn::make('etd')
                    ->label('ETD')
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('eta')
                    ->label('ETA')
                    ->dateTime()
                    ->sortable(),

                IconColumn::make('final_tam')
                    ->label('Final TAM')
                    ->boolean()
                    ->getStateUsing(
                        fn(Voyage $record) =>
                        optional($record->schedule)->state === ScheduleState::Final
                    ),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('generateSchedule')
                    ->label('Generate Schedule')
                    ->icon('heroicon-o-calendar')
                    ->requiresConfirmation()
                    ->action(fn(Voyage $record) => CreateFromVoyage::run($record)),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            ScheduleRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListVoyages::route('/'),
            'create' => Pages\CreateVoyage::route('/create'),
            'edit'   => Pages\EditVoyage::route('/{record}/edit'),
        ];
    }
}
