<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VesselResource\Pages;
use App\Models\ShippingLine;
use App\Models\Vessel;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;

class VesselResource extends Resource
{
    protected static ?string $model = Vessel::class;
    protected static ?string $navigationGroup = 'Pelayaran & Kapal';
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Kapal';

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Forms\Components\Select::make('shipping_line_id')
                ->label('Shipping Line')
                ->options(ShippingLine::query()->orderBy('name')->pluck('name', 'id'))
                ->searchable()
                ->preload()
                ->required(),
            Forms\Components\TextInput::make('name')->label('Nama Kapal')->required()->maxLength(120),
            Forms\Components\TextInput::make('imo')->label('IMO')->maxLength(30)->nullable(),
            Forms\Components\Toggle::make('is_active')->label('Aktif')->inline(false)->default(true),
        ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('shippingLine.name')->label('Shipping Line')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('name')->label('Nama Kapal')->sortable()->searchable(),
                Tables\Columns\IconColumn::make('is_active')->boolean()->label('Aktif'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('shipping_line_id')->label('Shipping Line')->options(
                    ShippingLine::orderBy('name')->pluck('name', 'id')
                ),
                Tables\Filters\TernaryFilter::make('is_active')->label('Status'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()->visible(fn(Vessel $r) => $r->schedules()->count() === 0),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
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
