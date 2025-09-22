<?php

namespace App\Filament\Resources;

use App\Enums\ArmadaStatus;
use App\Enums\ArmadaType;
use App\Filament\Resources\ArmadaResource\Pages;
use App\Filament\Resources\ArmadaResource\RelationManagers\AssignmentsRelationManager;
use App\Filament\Resources\ArmadaResource\RelationManagers\MaintenancesRelationManager;
use App\Models\Armada;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\View;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\View as ComponentsView;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Exceptions\Halt;
use Filament\Tables;
use Filament\Tables\Actions\Action;

class ArmadaResource extends Resource
{
    protected static ?string $model = Armada::class;

    protected static ?string $navigationGroup = 'Manajemen Armada & MP';
    protected static ?string $navigationLabel = 'Armada';
    protected static ?string $modelLabel = 'Armada';
    protected static ?string $pluralModelLabel = 'Armada';
    protected static ?string $navigationIcon = 'heroicon-m-truck';

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Select::make('type')->label('Tipe')
                ->label('Tipe')
                ->options(collect(\App\Enums\ArmadaType::cases())->mapWithKeys(fn($c) => [$c->value => $c->label()]))
                ->required()
                ->live()
                ->afterStateUpdated(function (Set $set, Get $get, $state) {
                    $prefix = Armada::resolvePrefixFromTypeValue((string) $state);
                    $set('code', Armada::previewNextCode($prefix, pad: 3));
                }),
            TextInput::make('code')
                ->label('Kode')
                ->disabled()
                ->dehydrated()
                ->helperText('Diisi otomatis berdasarkan Tipe'),
            TextInput::make('plate_number')->label('No. Polisi'),
            TextInput::make('capacity')->numeric()->label('Kapasitas'),

            ComponentsView::make('components.form-armada-status-badge')
                ->label('Status')
                ->viewData(
                    fn(Get $get, ?Armada $record) => [
                        'label' => $record?->status?->label() ?? ArmadaStatus::Available->label(),
                        'color' => $record?->status?->color() ?? 'success',
                    ]
                )
                ->columnSpanFull(),

            Select::make('branch_id')->relationship('branch', 'name')->label('Cabang')->required(),
            Select::make('depot_id')->relationship('depot', 'name')->label('Depot'),
            Textarea::make('notes')->label('Catatan')->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('code')
                ->badge()
                ->label('Kode')
                ->searchable(),
            Tables\Columns\TextColumn::make('type')
                ->label('Tipe')->badge()
                ->state(fn($record) => is_object($record->type) && method_exists($record->type, 'label') ? $record->type->label() : (string) $record->type),
            Tables\Columns\TextColumn::make('plate_number')
                ->label('No. Polisi'),
            Tables\Columns\TextColumn::make('capacity')
                ->label('Kapasitas')
                ->numeric(),
            Tables\Columns\TextColumn::make('status')->label('Status')->badge()
                ->color(fn($record) => is_object($record->status) && method_exists($record->status, 'color') ? $record->status->color() : 'gray')
                ->state(fn($record) => is_object($record->status) && method_exists($record->status, 'label') ? $record->status->label() : (string) $record->status),
            Tables\Columns\TextColumn::make('branch.name')
                ->label('Cabang')
                ->badge(),
            Tables\Columns\TextColumn::make('depot.name')
                ->label('Depot'),
            Tables\Columns\TextColumn::make('updated_at')
                ->since()
                ->label('Diubah'),
        ])->filters([
            Tables\Filters\SelectFilter::make('type')
                ->options(collect(ArmadaType::cases())->mapWithKeys(fn($c) => [$c->value => $c->label()])),
            Tables\Filters\SelectFilter::make('status')
                ->options(collect(ArmadaStatus::cases())->mapWithKeys(fn($c) => [$c->value => $c->label()])),
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
