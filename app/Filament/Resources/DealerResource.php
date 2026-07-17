<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DealerResource\Pages;
use App\Models\Dealer;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * DOMAIN-02 — Master Dealer (Vehicle Shipment).
 *
 * Dealer = jaringan distribusi milik Commercial Customer. Operational
 * Master, global (tanpa branch scope) — pola akses identik dengan
 * Pelanggan: office users kelola harian, delete milik Super Admin.
 */
class DealerResource extends Resource
{
    protected static ?string $model = Dealer::class;

    protected static ?string $navigationIcon   = 'heroicon-m-building-storefront';
    protected static ?string $navigationLabel  = 'Dealer';
    protected static ?string $pluralModelLabel = 'Dealer';
    protected static ?string $modelLabel       = 'Dealer';
    protected static ?string $navigationGroup  = 'Master Data';
    protected static ?int    $navigationSort   = 2;

    public static function canViewAny(): bool
    {
        return auth_user()?->isOfficeUser() ?? false;
    }

    public static function canCreate(): bool
    {
        return auth_user()?->isOfficeUser() ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth_user()?->isOfficeUser() ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth_user()?->isSuperAdmin() ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Data Dealer')
                ->columns(12)
                ->schema([
                    Select::make('customer_id')
                        ->label('Customer (pemilik jaringan)')
                        ->relationship('customer', 'name')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->columnSpan(6),
                    TextInput::make('name')
                        ->label('Nama Dealer')
                        ->placeholder('PT. Hasjrat Abadi')
                        ->required()
                        ->maxLength(150)
                        ->columnSpan(6),
                    TagsInput::make('aliases')
                        ->label('Alias (pencocokan dokumen/OCR)')
                        ->placeholder('PT. HA KOTAMOBAGU')
                        ->helperText('Sebutan lain dealer ini pada dokumen SPPB. Pencocokan mengabaikan prefiks PT/CV dan tanda baca.')
                        ->columnSpan(8),
                    Toggle::make('is_active')
                        ->label('Aktif')
                        ->default(true)
                        ->inline(false)
                        ->columnSpan(4),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Dealer')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('customer.name')
                    ->label('Customer')
                    ->sortable(),
                TextColumn::make('aliases')
                    ->label('Alias')
                    ->formatStateUsing(fn ($state) => is_array($state) ? implode(', ', $state) : (string) $state)
                    ->limit(60)
                    ->toggleable(),
                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('customer_id')
                    ->label('Customer')
                    ->relationship('customer', 'name'),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListDealers::route('/'),
            'create' => Pages\CreateDealer::route('/create'),
            'edit'   => Pages\EditDealer::route('/{record}/edit'),
        ];
    }
}
