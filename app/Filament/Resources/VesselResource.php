<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VesselResource\Pages;
use App\Models\Vessel;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class VesselResource extends Resource
{
    protected static ?string $model = Vessel::class;
    protected static ?string $navigationGroup = 'Master Data Shipment';
    protected static ?string $navigationIcon = 'heroicon-o-lifebuoy';
    protected static ?string $navigationLabel = 'Kapal';
    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('shipping_line_id')->relationship('shippingLine', 'name')->required()->searchable(),
            Forms\Components\TextInput::make('name')->required(),
            Forms\Components\TextInput::make('code')->maxLength(20),
            Forms\Components\TextInput::make('imo')->maxLength(20),
            Forms\Components\TextInput::make('capacity')->numeric()->default(0),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('period_month')
                    ->label('Periode')
                    ->date('M Y')
                    ->sortable(),

                TextColumn::make('route_code')
                    ->label('Rute'),

                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn($state) => $state->label())
                    ->color(fn($state) => $state->color()),

                TextColumn::make('max_gap')
                    ->label('Max ETD Gap')
                    ->getStateUsing(
                        fn($record) =>
                        $record->maxEtdGap() . ' hari'
                    )
                    ->color(
                        fn($record) =>
                        $record->maxEtdGap() > 6 ? 'danger' : 'success'
                    ),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->defaultSort('period_month', 'desc');
    }


    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVessels::route('/'),
            'create' => Pages\CreateVessel::route('/create'),
            'edit' => Pages\EditVessel::route('/{record}/edit'),
        ];
    }
}
