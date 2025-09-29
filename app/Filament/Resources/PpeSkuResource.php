<?php

namespace App\Filament\Resources;

use App\Enums\PpeType;
use App\Filament\Resources\PpeSkuResource\Pages;
use App\Models\PpeSku;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

class PpeSkuResource extends Resource
{
    protected static ?string $model = PpeSku::class;

    protected static ?string $navigationGroup = 'APD & K3';
    protected static ?string $navigationLabel = 'Master SKU APD';
    protected static ?string $pluralLabel     = 'Master SKU APD';
    protected static ?string $modelLabel      = 'SKU APD';
    protected static ?string $navigationIcon  = 'heroicon-m-rectangle-stack';
    protected static ?int    $navigationSort  = 10;

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            TextInput::make('code')
                ->label('Kode')
                ->disabled()
                ->dehydrated(false)
                ->hint('Otomatis saat simpan'),

            TextInput::make('name')
                ->label('Nama')
                ->required()
                ->maxLength(100),

            Select::make('type')
                ->label('Jenis')
                ->options(collect(PpeType::cases())->mapWithKeys(fn($c) => [$c->value => $c->label()]))
                ->required()
                ->searchable()
                ->preload(),

            TextInput::make('brand')->label('Merek')->maxLength(100),
            TextInput::make('model')->label('Model')->maxLength(100),
            TextInput::make('size')->label('Ukuran')->maxLength(50),

            Toggle::make('is_serialized')
                ->label('Pakai Nomor Seri')
                ->default(true),

            TextInput::make('min_qty')
                ->label('Min. Stok')
                ->numeric(),
        ])->columns(2);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->query(fn(): EloquentBuilder => static::getEloquentQuery())
            ->defaultSort('name')
            ->columns([
                TextColumn::make('code')->label('Kode')->searchable()->sortable(),
                TextColumn::make('name')->label('Nama')->searchable()->sortable(),
                TextColumn::make('type')
                    ->label('Jenis')
                    ->formatStateUsing(fn($state) => \App\Enums\PpeType::tryFrom($state)?->label() ?? $state)
                    ->badge()
                    ->sortable(),
                TextColumn::make('brand')->label('Merek')->toggleable(),
                TextColumn::make('model')->label('Model')->toggleable(),
                TextColumn::make('size')->label('Ukuran')->toggleable(),
                TextColumn::make('is_serialized')
                    ->label('Serialized')
                    ->formatStateUsing(fn($state) => $state ? 'Ya' : 'Tidak')
                    ->badge()
                    ->sortable(),
                TextColumn::make('items_count')->label('Item')->sortable(),
                TextColumn::make('items_in_stock')->label('Stok')->sortable(),
                TextColumn::make('items_assigned')->label('Dipakai')->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')->label('Jenis')->options(collect(PpeType::cases())->mapWithKeys(fn($c) => [$c->value => $c->label()])),
                SelectFilter::make('is_serialized')->label('Serialized')->options([1 => 'Ya', 0 => 'Tidak']),
                Filter::make('low_stock')
                    ->label('Stok < Min')
                    ->query(fn(EloquentBuilder $query) => $query->whereColumn('items_in_stock', '<', 'min_qty')),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('Ubah'),
                Tables\Actions\DeleteAction::make()->label('Hapus'),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()->label('Hapus Terpilih'),
            ]);
    }

    public static function getEloquentQuery(): EloquentBuilder
    {
        return static::getModel()::query()
            ->withCount([
                'items',
                'items as items_in_stock' => fn(EloquentBuilder $query) => $query->where('status', 'in_stock'),
                'items as items_assigned' => fn(EloquentBuilder $query) => $query->where('status', 'assigned'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListPpeSkus::route('/'),
            'create' => Pages\CreatePpeSku::route('/create'),
            'edit'   => Pages\EditPpeSku::route('/{record}/edit'),
        ];
    }
}
