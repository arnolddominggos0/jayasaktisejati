<?php

namespace App\Filament\Cms\Resources;

use App\Filament\Cms\Resources\GalleryItemResource\Pages;
use App\Models\JslGalleryItem;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class GalleryItemResource extends Resource
{
    protected static ?string $model = JslGalleryItem::class;

    protected static ?string $navigationGroup = 'Website Content';
    protected static ?string $navigationIcon = 'heroicon-o-photo';
    protected static ?string $navigationLabel = 'Gallery';
    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Gallery Item')
                ->schema([
                    Forms\Components\Select::make('media_asset_id')
                        ->label('Media Asset')
                        ->relationship('mediaAsset', 'file_name')
                        ->searchable()
                        ->required(),
                    Forms\Components\TextInput::make('caption')
                        ->label('Caption (ID)'),
                    Forms\Components\TextInput::make('caption_en')
                        ->label('Caption (EN)'),
                    Forms\Components\TextInput::make('category')
                        ->label('Category')
                        ->helperText('e.g. Operations, Fleet, Port, Event'),
                    Forms\Components\TextInput::make('sort_order')
                        ->numeric()
                        ->default(0),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('caption')
                    ->label('Caption (ID)')
                    ->searchable(),
                Tables\Columns\TextColumn::make('category')
                    ->label('Category')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Order')
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->defaultSort('sort_order');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGalleryItems::route('/'),
            'create' => Pages\CreateGalleryItem::route('/create'),
            'edit' => Pages\EditGalleryItem::route('/{record}/edit'),
        ];
    }
}
