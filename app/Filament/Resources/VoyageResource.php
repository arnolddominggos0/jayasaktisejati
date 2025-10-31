<?php

namespace App\Filament\Resources;

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
            Forms\Components\Select::make('vessel_id')->relationship('vessel', 'name')->required()->searchable(),
            Forms\Components\Select::make('pol_id')->relationship('pol', 'name')->required()->searchable()->label('POL'),
            Forms\Components\Select::make('pod_id')->relationship('pod', 'name')->required()->searchable()->label('POD'),
            Forms\Components\TextInput::make('voyage_no')->required()->maxLength(50),
            Forms\Components\TextInput::make('service')->maxLength(50),
            Forms\Components\DateTimePicker::make('etd')->required(),
            Forms\Components\DateTimePicker::make('eta')->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('vessel.shippingLine.name')->label('Shipping Line')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('vessel.name')->label('Kapal')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('voyage_no')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('pol.code')->label('POL')->sortable(),
            Tables\Columns\TextColumn::make('pod.code')->label('POD')->sortable(),
            Tables\Columns\TextColumn::make('etd')->dateTime()->sortable(),
            Tables\Columns\TextColumn::make('eta')->dateTime()->sortable(),
            Tables\Columns\IconColumn::make('schedule.state')->boolean()
                ->getStateUsing(fn($record) => optional($record->schedule)->state?->value === 'final')
                ->label('Final TAM'),
        ])->actions([
            Tables\Actions\EditAction::make(),
        ])->bulkActions([
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
            'index' => Pages\ListVoyages::route('/'),
            'create' => Pages\CreateVoyage::route('/create'),
            'edit' => Pages\EditVoyage::route('/{record}/edit'),
        ];
    }
}
