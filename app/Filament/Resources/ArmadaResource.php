<?php

namespace App\Filament\Resources;

use App\Enums\ArmadaStatus;
use App\Enums\ArmadaType;
use App\Filament\Resources\ArmadaResource\Pages;
use App\Filament\Resources\ArmadaResource\RelationManagers\AssignmentsRelationManager;
use App\Filament\Resources\ArmadaResource\RelationManagers\MaintenancesRelationManager;
use App\Models\Armada;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;

class ArmadaResource extends Resource
{
    protected static ?string $model = Armada::class;
    protected static ?string $navigationGroup = 'Manajemen Armada & MP';
    protected static ?string $navigationIcon = 'heroicon-m-truck';
    protected static ?string $modelLabel = 'Armada';

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('code')->label('Kode')->required()->unique(ignoreRecord: true),
            Forms\Components\Select::make('type')->label('Tipe')
                ->options(collect(ArmadaType::cases())->mapWithKeys(fn($c) => [$c->value => $c->label()]))->required(),
            Forms\Components\TextInput::make('plate_number')->label('No. Polisi'),
            Forms\Components\TextInput::make('capacity')->numeric()->label('Kapasitas'),
            Forms\Components\Select::make('status')->label('Status')
                ->options(collect(ArmadaStatus::cases())->mapWithKeys(fn($c) => [$c->value => $c->label()]))->required(),
            Forms\Components\Select::make('branch_id')->relationship('branch', 'name')->label('Cabang')->required(),
            Forms\Components\Select::make('depot_id')->relationship('depot', 'name')->label('Depot'),
            Forms\Components\Textarea::make('notes')->label('Catatan')->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('code')
                ->badge()
                ->label('Kode')
                ->searchable(),

            // TYPE (pakai ArmadaType, bukan ArmadaStatus)
            Tables\Columns\TextColumn::make('type')
                ->label('Tipe')
                ->badge()
                ->state(
                    fn($record) =>
                    // kalau sudah cast enum: panggil label(); kalau belum: fallback string
                    (is_object($record->type) && method_exists($record->type, 'label'))
                        ? $record->type->label()
                        : (string) $record->type
                ),

            Tables\Columns\TextColumn::make('plate_number')->label('No. Polisi'),

            Tables\Columns\TextColumn::make('capacity')
                ->label('Kapasitas')
                ->numeric(),

            // STATUS
            Tables\Columns\TextColumn::make('status')
                ->label('Status')
                ->badge()
                ->color(
                    fn($record) => (is_object($record->status) && method_exists($record->status, 'color'))
                        ? $record->status->color()
                        : 'gray'
                )
                ->state(
                    fn($record) => (is_object($record->status) && method_exists($record->status, 'label'))
                        ? $record->status->label()
                        : (string) $record->status
                ),

            Tables\Columns\TextColumn::make('branch.name')->label('Cabang')->badge(),
            Tables\Columns\TextColumn::make('depot.name')->label('Depot'),
            Tables\Columns\TextColumn::make('updated_at')->since()->label('Diubah'),
        ])->filters([
            Tables\Filters\SelectFilter::make('type')
                ->options(collect(\App\Enums\ArmadaType::cases())->mapWithKeys(fn($c) => [$c->value => $c->label()])),
            Tables\Filters\SelectFilter::make('status')
                ->options(collect(\App\Enums\ArmadaStatus::cases())->mapWithKeys(fn($c) => [$c->value => $c->label()])),
        ])->actions([
            Tables\Actions\EditAction::make()->label('Ubah'),
        ])->bulkActions([
            Tables\Actions\DeleteBulkAction::make()->label('Hapus'),
        ]);
    }


    public static function getRelations(): array
    {
        return [
            MaintenancesRelationManager::class,
            AssignmentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListArmadas::route('/'),
            'create' => Pages\CreateArmada::route('/create'),
            'edit'   => Pages\EditArmada::route('/{record}/edit'),
        ];
    }
}
