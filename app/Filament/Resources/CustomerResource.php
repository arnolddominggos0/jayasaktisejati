<?php

namespace App\Filament\Resources;

use App\Enums\CustomerType;
use App\Filament\Resources\CustomerResource\Pages;
use App\Models\Customer;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static ?string $navigationIcon   = 'heroicon-m-users';
    protected static ?string $navigationLabel  = 'Pelanggan';
    protected static ?string $pluralModelLabel = 'Pelanggan';
    protected static ?string $modelLabel       = 'Pelanggan';
    protected static ?string $navigationGroup  = 'Master Data';
    protected static ?int    $navigationSort   = 5;

    public static function canViewAny(): bool
    {
        return auth_user()?->hasRole('super_admin') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Group::make()->schema([
                Section::make('Data Customer')
                    ->schema([
                        ToggleButtons::make('type')
                            ->label('Tipe Customer')
                            ->options([
                                CustomerType::Individual->value => CustomerType::Individual->label(),
                                CustomerType::Company->value    => CustomerType::Company->label(),
                            ])
                            ->icons([
                                CustomerType::Individual->value => 'heroicon-m-user',
                                CustomerType::Company->value    => 'heroicon-m-building-office-2',
                            ])
                            ->colors([
                                CustomerType::Individual->value => 'primary',
                                CustomerType::Company->value    => 'success',
                            ])
                            ->inline()
                            ->required()
                            ->default(CustomerType::Individual->value)
                            ->live()
                            ->afterStateUpdated(function (string $state, Set $set) {
                                if ($state === CustomerType::Company->value) {
                                    $set('nik', null);
                                } else {
                                    $set('npwp', null);
                                }
                            }),

                        TextInput::make('code')
                            ->label('Kode Customer')
                            ->disabled()
                            ->dehydrated(false)
                            ->hiddenOn('create'),

                        TextInput::make('name')
                            ->label(fn(Get $get) => $get('type') === CustomerType::Company->value ? 'Nama Perusahaan' : 'Nama Lengkap')
                            ->required()
                            ->maxLength(150)
                            ->live(debounce: 500)
                            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                if ($get('pic_same')) {
                                    $set('pic_name', $state);
                                }
                            }),

                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->maxLength(150)
                            ->live(debounce: 500)
                            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                if ($get('pic_same')) {
                                    $set('pic_email', $state);
                                }
                            }),

                        TextInput::make('phone')
                            ->label('No. Telepon')
                            ->maxLength(30)
                            ->live(debounce: 500)
                            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                if ($get('pic_same')) {
                                    $set('pic_phone', $state);
                                }
                            }),

                        TextInput::make('nik')
                            ->label('NIK')
                            ->visible(fn(Get $get) => $get('type') === CustomerType::Individual->value)
                            ->required(fn(Get $get) => $get('type') === CustomerType::Individual->value)
                            ->rule(fn(Get $get) => $get('type') === CustomerType::Individual->value ? 'digits:16' : 'nullable')
                            ->mutateDehydratedStateUsing(fn($state) => $state ? preg_replace('/\D+/', '', (string) $state) : null)
                            ->maxLength(16),

                        TextInput::make('npwp')
                            ->label('NPWP')
                            ->visible(fn(Get $get) => $get('type') === CustomerType::Company->value)
                            ->required(fn(Get $get) => $get('type') === CustomerType::Company->value)
                            ->rule(fn(Get $get) => $get('type') === CustomerType::Company->value ? 'max:32' : 'nullable')
                            ->mutateDehydratedStateUsing(fn($state) => $state ? trim((string) $state) : null)
                            ->maxLength(32),
                    ])->columns(2),

                Section::make('Kontak & Alamat')->schema([
                    Checkbox::make('pic_same')
                        ->label('PIC sama dengan data utama')
                        ->default(true)
                        ->dehydrated(false)
                        ->live()
                        ->afterStateUpdated(function (bool $state, Set $set, Get $get) {
                            if ($state) {
                                $set('pic_name',  $get('name'));
                                $set('pic_email', $get('email'));
                                $set('pic_phone', $get('phone'));
                            }
                        })
                        ->columnSpan(2),

                    TextInput::make('pic_name')
                        ->label('PIC / Kontak *')
                        ->required()
                        ->maxLength(100)
                        ->disabled(fn(Get $get) => (bool) $get('pic_same'))
                        ->columnSpan(4),

                    TextInput::make('pic_phone')
                        ->label('No. Telp/WA *')
                        ->tel()
                        ->required()
                        ->maxLength(20)
                        ->disabled(fn(Get $get) => (bool) $get('pic_same'))
                        ->columnSpan(2),

                    TextInput::make('pic_email')
                        ->label('Email PIC')
                        ->email()
                        ->maxLength(150)
                        ->required(fn(Get $get) => $get('type') === CustomerType::Company->value && ! $get('pic_same'))
                        ->disabled(fn(Get $get) => (bool) $get('pic_same')),

                    Textarea::make('address')->label('Alamat')->rows(3)->columnSpanFull(),
                ])->columns(2),
            ])->columnSpan(['lg' => 2]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->label('Kode')->badge()->searchable()->sortable()->copyable(),

                TextColumn::make('type')
                    ->label('Tipe')
                    ->badge()
                    ->formatStateUsing(function ($state) {
                        $enum = $state instanceof CustomerType ? $state : CustomerType::tryFrom((string) $state);
                        return $enum?->label() ?? '-';
                    })
                    ->colors([
                        'success' => fn($state) => ($state instanceof CustomerType ? $state->value : (string) $state) === CustomerType::Company->value,
                        'primary' => fn($state) => ($state instanceof CustomerType ? $state->value : (string) $state) === CustomerType::Individual->value,
                    ])
                    ->sortable(),

                TextColumn::make('name')->label('Nama')->searchable()->sortable(),
                TextColumn::make('email')->label('Email')->toggleable()->searchable(),
                TextColumn::make('phone')->label('Telepon')->toggleable()->searchable(),
                TextColumn::make('pic_name')->label('PIC')->toggleable(),
                TextColumn::make('updated_at')->label('Diubah')->since()->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Tipe')
                    ->options([
                        CustomerType::Individual->value => CustomerType::Individual->label(),
                        CustomerType::Company->value    => CustomerType::Company->label(),
                    ]),
                Filter::make('has_email')
                    ->label('Punya Email')
                    ->query(fn($query) => $query->whereNotNull('email')),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('Ubah'),
                Tables\Actions\DeleteAction::make()->label('Hapus'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->label('Hapus Terpilih'),
                ]),
            ])
            ->defaultSort('updated_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListCustomers::route('/'),
            'create' => Pages\CreateCustomer::route('/create'),
            'edit'   => Pages\EditCustomer::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) Customer::count();
    }
}
