<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PpeItemResource\Pages;
use App\Models\Manpower;
use App\Models\PpeItem;
use App\Models\PpeSku;
use Filament\Forms;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Validation\Rule;

class PpeItemResource extends Resource
{
    protected static ?string $model = PpeItem::class;

    protected static ?string $navigationGroup = 'APD & K3';
    protected static ?string $navigationLabel = 'Item APD';
    protected static ?string $pluralLabel     = 'Item APD';
    protected static ?string $modelLabel      = 'Item APD';
    protected static ?string $navigationIcon  = 'heroicon-m-cube';
    protected static ?int    $navigationSort  = 11;

    private const STATUSES = [
        'in_stock' => 'In Stock',
        'assigned' => 'Assigned',
        'damaged'  => 'Rusak',
        'lost'     => 'Hilang',
        'disposed' => 'Disposed',
    ];

    private const STATUS_COLORS = [
        'in_stock' => 'success',
        'assigned' => 'warning',
        'damaged'  => 'danger',
        'lost'     => 'danger',
        'disposed' => 'gray',
    ];

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Select::make('ppe_sku_id')
                ->label('SKU')
                ->relationship('sku','name', fn(EloquentBuilder $query) => $query->orderBy('name'))
                ->required()
                ->searchable()
                ->preload()
                ->reactive(),
            TextInput::make('serial')
                ->label('Nomor Seri')
                ->maxLength(100)
                ->visible(fn(Get $get) => (bool) PpeSku::whereKey($get('ppe_sku_id'))->value('is_serialized'))
                ->required(fn(Get $get) => (bool) PpeSku::whereKey($get('ppe_sku_id'))->value('is_serialized'))
                ->rule(function (Get $get, ?PpeItem $record) {
                    $isSer = (bool) PpeSku::whereKey($get('ppe_sku_id'))->value('is_serialized');
                    if (! $isSer) return null;
                    return Rule::unique('ppe_items','serial')->ignore($record?->id);
                }),
            Select::make('status')
                ->label('Status')
                ->options(self::STATUSES)
                ->default('in_stock')
                ->required()
                ->reactive(),
            Select::make('current_manpower_id')
                ->label('Dipakai Oleh')
                ->options(fn() => Manpower::query()->orderBy('name')->pluck('name','id'))
                ->searchable()
                ->preload()
                ->visible(fn(Get $get) => $get('status') === 'assigned')
                ->required(fn(Get $get) => $get('status') === 'assigned'),
            DateTimePicker::make('assigned_at')
                ->label('Tgl. Penugasan')
                ->seconds(false)
                ->default(now())
                ->visible(fn(Get $get) => $get('status') === 'assigned')
                ->required(fn(Get $get) => $get('status') === 'assigned'),
        ])->columns(2);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->query(fn(): EloquentBuilder => static::getEloquentQuery())
            ->defaultSort('created_at','desc')
            ->columns([
                TextColumn::make('sku.code')->label('Kode SKU')->sortable()->searchable(),
                TextColumn::make('sku.name')->label('Nama SKU')->sortable()->searchable(),
                TextColumn::make('sku.type')->label('Jenis')->badge()->sortable(),
                TextColumn::make('serial')->label('Serial')->searchable(),
                TextColumn::make('status')->label('Status')->badge()->color(fn($state) => self::STATUS_COLORS[$state] ?? 'gray')->sortable(),
                TextColumn::make('currentManpower.name')->label('Dipakai Oleh')->toggleable(),
                TextColumn::make('assigned_at')->label('Tgl. Penugasan')->dateTime()->toggleable()->sortable(),
                TextColumn::make('created_at')->label('Dibuat')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')->label('Status')->options(self::STATUSES),
                SelectFilter::make('ppe_sku_id')->label('SKU')->relationship('sku','name'),
                SelectFilter::make('sku.type')->label('Jenis')->relationship('sku','type')->options(
                    collect(\App\Enums\PpeType::cases())->mapWithKeys(fn($c) => [$c->value => $c->label()])
                ),
                Filter::make('is_serialized')->label('Serialized')->query(fn(EloquentBuilder $query) => $query->whereHas('sku', fn($s) => $s->where('is_serialized', true))),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('Ubah'),
                Tables\Actions\DeleteAction::make()->label('Hapus'),
            ])
            ->bulkActions([Tables\Actions\DeleteBulkAction::make()->label('Hapus Terpilih')]);
    }

    public static function getEloquentQuery(): EloquentBuilder
    {
        return static::getModel()::query()->with(['sku:id,name,code,type,is_serialized','currentManpower:id,name']);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListPpeItems::route('/'),
            'create' => Pages\CreatePpeItem::route('/create'),
            'edit'   => Pages\EditPpeItem::route('/{record}/edit'),
        ];
    }
}
