<?php

namespace App\Filament\Resources;

use App\Enums\DepotMetric;
use App\Filament\Resources\DepotActivityResource\Pages;
use App\Models\DepotActivity;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Tables;

class DepotActivityResource extends Resource
{
    protected static ?string $model = DepotActivity::class;

    protected static ?string $navigationGroup = 'Manajemen Armada & MP';
    protected static ?string $navigationLabel = 'Aktivitas Depo';
    protected static ?string $pluralLabel = 'Aktivitas Depo';
    protected static ?string $modelLabel = 'Aktivitas Depo';
    protected static ?string $navigationIcon = 'heroicon-m-chart-bar';

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Forms\Components\DatePicker::make('date')->label('Tanggal')->required(),
            Forms\Components\Select::make('depot_id')->relationship('depot', 'name')->label('Depot')->required(),
            Select::make('metric')
                ->label('Metrik')
                ->options(
                    collect(DepotMetric::cases())
                        ->mapWithKeys(fn($c) => [$c->value => $c->label()])
                )
                ->required()
                ->native(false),
            Forms\Components\TextInput::make('value')->numeric()->label('Nilai')->default(0),
            Forms\Components\TextInput::make('remark')->label('Catatan'),
        ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('date')->date()->label('Tanggal'),
            Tables\Columns\TextColumn::make('depot.name')->label('Depot'),
            Tables\Columns\TextColumn::make('metric')->label('Metrik')->badge(),
            Tables\Columns\TextColumn::make('value')->label('Nilai')->badge(),
            Tables\Columns\TextColumn::make('remark')->label('Catatan')->limit(30),
        ])->actions([
            Tables\Actions\EditAction::make(),
        ])->bulkActions([
            Tables\Actions\DeleteBulkAction::make(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListDepotActivities::route('/'),
            'create' => Pages\CreateDepotActivity::route('/create'),
            'edit'   => Pages\EditDepotActivity::route('/{record}/edit'),
        ];
    }
}
