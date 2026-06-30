<?php

namespace App\Filament\Cms\Resources;

use App\Filament\Cms\Resources\ServiceResource\Pages;
use App\Models\JslService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ServiceResource extends Resource
{
    protected static ?string $model = JslService::class;

    protected static ?string $navigationGroup = 'Website Content';
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Services';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Service Details')
                ->schema([
                    Forms\Components\TextInput::make('title')
                        ->label('Title (ID)')
                        ->required(),
                    Forms\Components\TextInput::make('title_en')
                        ->label('Title (EN)'),
                    Forms\Components\RichEditor::make('description')
                        ->label('Description (ID)')
                        ->columnSpanFull(),
                    Forms\Components\RichEditor::make('description_en')
                        ->label('Description (EN)')
                        ->columnSpanFull(),
                    Forms\Components\Toggle::make('is_visible')
                        ->label('Visible on website')
                        ->default(true),
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
                Tables\Columns\TextColumn::make('title')
                    ->label('Title (ID)')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('title_en')
                    ->label('Title (EN)')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_visible')
                    ->label('Visible')
                    ->boolean(),
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
            'index' => Pages\ListServices::route('/'),
            'create' => Pages\CreateService::route('/create'),
            'edit' => Pages\EditService::route('/{record}/edit'),
        ];
    }
}
