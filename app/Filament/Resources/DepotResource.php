<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DepotResource\Pages;
use App\Models\Depot;
use App\Models\User;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;

class DepotResource extends Resource
{
    protected static ?string $model = Depot::class;

    protected static ?string $navigationGroup = 'Manajemen Armada';
    protected static ?string $navigationLabel = 'Depo';
    protected static ?string $pluralLabel = 'Depo';
    protected static ?string $navigationIcon = 'heroicon-m-building-office-2';
    protected static ?string $modelLabel = 'Depo';
    protected static ?int    $navigationSort  = 40;

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('code')->label('Kode')->required()->unique(ignoreRecord: true),
            Forms\Components\TextInput::make('name')->label('Nama')->required(),
            Forms\Components\Select::make('mode')->label('Moda')
                ->options(['sea_freight'=>'Laut','land_trucking'=>'Darat'])->required(),
            Forms\Components\Textarea::make('address')->label('Alamat')->columnSpanFull(),
            Forms\Components\Select::make('branch_id')->relationship('branch','name')->label('Cabang')->required(),
            Forms\Components\Select::make('coordinator_user_id')->label('Koordinator Lapangan')
                ->options(
                    User::role('field_coordinator')->orderBy('name')->pluck('name','id')
                )
                ->searchable()
                ->helperText('Pengguna dengan peran "Koordinator Lapangan".'),
        ])->columns(2);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('code')->badge()->label('Kode')->searchable(),
            Tables\Columns\TextColumn::make('name')->label('Nama')->searchable(),
            Tables\Columns\TextColumn::make('mode')->badge()->label('Moda')
                ->formatStateUsing(fn($state)=>$state==='sea_freight'?'Laut':'Darat'),
            Tables\Columns\TextColumn::make('branch.name')->label('Cabang')->badge(),
            Tables\Columns\TextColumn::make('coordinator.name')->label('Koordinator'),
            Tables\Columns\TextColumn::make('updated_at')->since()->label('Diubah'),
        ])->actions([
            Tables\Actions\EditAction::make()->label('Ubah'),
        ])->bulkActions([
            Tables\Actions\DeleteBulkAction::make()->label('Hapus'),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListDepots::route('/'),
            'create' => Pages\CreateDepot::route('/create'),
            'edit'   => Pages\EditDepot::route('/{record}/edit'),
        ];
    }
}
