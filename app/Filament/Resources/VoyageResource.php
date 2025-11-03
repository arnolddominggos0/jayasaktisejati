<?php

namespace App\Filament\Resources;

use App\Enums\ScheduleState;
use App\Filament\Resources\VoyageResource\Pages;
use App\Filament\Resources\VoyageResource\RelationManagers\ScheduleRelationManager;
use App\Models\Voyage;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class VoyageResource extends Resource
{
    protected static ?string $model = Voyage::class;
    protected static ?string $navigationGroup = 'Operasional Kapal';
    protected static ?string $navigationIcon = 'heroicon-o-paper-airplane';
    protected static ?string $navigationLabel = 'Voyage';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('vessel_id')->relationship('vessel', 'name')->required()->searchable()->label('Kapal'),
            Forms\Components\Select::make('pol_id')->relationship('pol', 'name')->required()->searchable()->label('POL'),
            Forms\Components\Select::make('pod_id')->relationship('pod', 'name')->required()->searchable()->label('POD'),
            Forms\Components\TextInput::make('voyage_no')->required()->maxLength(50)->label('Voyage No'),
            Forms\Components\TextInput::make('service')->maxLength(50)->label('Service'),
            Forms\Components\DateTimePicker::make('etd')->required()->label('ETD'),
            Forms\Components\DateTimePicker::make('eta')->required()->label('ETA'),
            Forms\Components\DateTimePicker::make('atd_at')->label('ATD (Actual)'),
            Forms\Components\DateTimePicker::make('ata_at')->label('ATA (Actual)'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('vessel.shippingLine.name')->label('Shipping Line')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('vessel.name')->label('Kapal')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('voyage_no')->label('Voyage')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('pol.code')->label('POL')->sortable(),
            Tables\Columns\TextColumn::make('pod.code')->label('POD')->sortable(),
            Tables\Columns\TextColumn::make('etd')->label('ETD')->dateTime()->sortable(),
            Tables\Columns\TextColumn::make('eta')->label('ETA')->dateTime()->sortable(),
            Tables\Columns\IconColumn::make('schedule.state')
                ->boolean()
                ->getStateUsing(fn($record) => optional($record->schedule)->state === ScheduleState::Final)
                ->label('Final TAM'),
        ])->actions([
            Tables\Actions\EditAction::make(),
        ])->bulkActions([
            Tables\Actions\DeleteBulkAction::make(),
        ]);
    }

    public static function getRelations(): array
    {
        return [ScheduleRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVoyages::route('/'),
            'create' => Pages\CreateVoyage::route('/create'),
            'edit' => Pages\EditVoyage::route('/{record}/edit'),
        ];
    }
}
