<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CityResource\Pages;
use App\Models\City;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class CityResource extends Resource
{
    protected static ?string $model = City::class;

    protected static ?string $navigationGroup = 'Master Data';
    protected static ?string $navigationIcon  = 'heroicon-o-map-pin';
    protected static ?string $navigationLabel = 'Kota';
    protected static ?string $pluralLabel     = 'Kota';
    protected static ?string $modelLabel      = 'Kota';
    protected static ?int    $navigationSort  = 7;

    public static function shouldRegisterNavigation(): bool
    {
        return auth_user()?->isSuperAdmin() ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Informasi Kota')->schema([
                TextInput::make('name')
                    ->label('Nama Kota')
                    ->required()
                    ->maxLength(100)
                    ->unique(ignoreRecord: true)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (string $operation, ?string $state, Set $set) {
                        if ($operation === 'create' && $state) {
                            $set('slug', Str::slug($state));
                        }
                    })
                    ->columnSpan(['default' => 12, 'md' => 6]),

                TextInput::make('slug')
                    ->label('Slug')
                    ->required()
                    ->maxLength(120)
                    ->unique(ignoreRecord: true)
                    ->regex('/^[a-z0-9\-]+$/')
                    ->helperText('Diisi otomatis. Hanya huruf kecil, angka, dan tanda hubung.')
                    ->columnSpan(['default' => 12, 'md' => 6]),

                TextInput::make('province')
                    ->label('Provinsi')
                    ->maxLength(100)
                    ->placeholder('cth. DKI Jakarta')
                    ->columnSpan(['default' => 12, 'md' => 6]),

                TextInput::make('country')
                    ->label('Negara')
                    ->required()
                    ->maxLength(100)
                    ->default('Indonesia')
                    ->columnSpan(['default' => 12, 'md' => 6]),

                Toggle::make('is_active')
                    ->label('Kota Aktif')
                    ->helperText('Kota nonaktif tidak muncul di pilihan rute pengiriman baru.')
                    ->default(true)
                    ->columnSpanFull(),
            ])->columns(12),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Kota')
                    ->sortable()
                    ->searchable()
                    ->weight('semibold'),

                TextColumn::make('province')
                    ->label('Provinsi')
                    ->sortable()
                    ->searchable()
                    ->placeholder('—'),

                TextColumn::make('country')
                    ->label('Negara')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('origin_shipments_count')
                    ->label('Sbg. Asal')
                    ->counts('originShipments')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('destination_shipments_count')
                    ->label('Sbg. Tujuan')
                    ->counts('destinationShipments')
                    ->badge()
                    ->color('primary'),

                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Status')
                    ->placeholder('Semua')
                    ->trueLabel('Aktif')
                    ->falseLabel('Nonaktif'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->before(function (City $record, Tables\Actions\DeleteAction $action) {
                        if ($record->hasActiveShipments()) {
                            Notification::make()
                                ->danger()
                                ->title('Tidak dapat menghapus kota')
                                ->body("Kota \"{$record->name}\" masih direferensikan oleh shipment. Nonaktifkan kota ini jika tidak ingin muncul di pilihan baru.")
                                ->persistent()
                                ->send();

                            $action->cancel();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->before(function (\Illuminate\Support\Collection $records, Tables\Actions\DeleteBulkAction $action) {
                            $blocked = $records->filter(fn (City $c) => $c->hasActiveShipments());
                            if ($blocked->isNotEmpty()) {
                                Notification::make()
                                    ->danger()
                                    ->title('Sebagian kota tidak dapat dihapus')
                                    ->body('Kota berikut masih direferensikan oleh shipment: '.$blocked->pluck('name')->join(', ').'.')
                                    ->persistent()
                                    ->send();

                                $action->cancel();
                            }
                        }),
                ]),
            ])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListCities::route('/'),
            'create' => Pages\CreateCity::route('/create'),
            'edit'   => Pages\EditCity::route('/{record}/edit'),
        ];
    }
}
