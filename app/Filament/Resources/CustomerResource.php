<?php

namespace App\Filament\Resources;

use App\Enums\CustomerType;
use App\Filament\Resources\CustomerResource\Pages;
use App\Models\Customer;
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

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationLabel = 'Pelanggan';
    protected static ?string $pluralModelLabel = 'Pelanggan';
    protected static ?string $modelLabel = 'Pelanggan';
    protected static ?string $navigationGroup = 'Manajemen Data';
    protected static ?int $navigationSort = 10;

    public static function canViewAny(): bool
    {
        return auth_user()?->hasAnyRole('super_admin', 'office_admin') ?? false;
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
                            ->live() // penting: memicu field lain update
                            ->afterStateUpdated(function ($state, Set $set) {
                                // Saat ganti tipe, kosongkan field yang tidak relevan biar data bersih
                                if ($state === CustomerType::Company->value) {
                                    $set('nik', null);
                                } else {
                                    $set('npwp', null);
                                }
                            }),

                        TextInput::make('code')
                            ->label('Kode Customer')
                            ->placeholder('CTM-0001')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(20),

                        TextInput::make('name')
                            ->label(
                                fn(Get $get) =>
                                $get('type') === CustomerType::Company->value ? 'Nama Perusahaan' : 'Nama Lengkap'
                            )
                            ->placeholder(
                                fn(Get $get) =>
                                $get('type') === CustomerType::Company->value ? 'PT Contoh Sejahtera' : 'Budi Santoso'
                            )
                            ->required()
                            ->maxLength(150),

                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->maxLength(150),

                        TextInput::make('phone')
                            ->label('No. Telepon')
                            ->maxLength(30),

                        TextInput::make('nik')
                            ->label('NIK')
                            ->visible(
                                fn(Get $get) =>
                                $get('type') === CustomerType::Individual->value
                            )
                            ->required(
                                fn(Get $get) =>
                                $get('type') === CustomerType::Individual->value
                            )
                            ->rule(
                                fn(Get $get) =>
                                $get('type') === CustomerType::Individual->value
                                    ? 'digits:16'
                                    : 'nullable'
                            )
                            // Simpan hanya angka
                            ->mutateDehydratedStateUsing(fn($state) => $state ? preg_replace('/\D+/', '', (string) $state) : null)
                            ->maxLength(16),

                        // === Kondisional: NPWP untuk Company ===
                        TextInput::make('npwp')
                            ->label('NPWP')
                            ->visible(
                                fn(Get $get) =>
                                $get('type') === CustomerType::Company->value
                            )
                            ->required(
                                fn(Get $get) =>
                                $get('type') === CustomerType::Company->value
                            )
                            ->rule(
                                fn(Get $get) =>
                                $get('type') === CustomerType::Company->value
                                    ? 'max:32'
                                    : 'nullable'
                            )
                            ->mutateDehydratedStateUsing(fn($state) => $state ? trim((string) $state) : null)
                            ->maxLength(32),
                    ])->columns(2),

                Section::make('Kontak & Alamat')->schema([
                    TextInput::make('pic_name')->label('Nama PIC')->maxLength(100),
                    TextInput::make('pic_phone')->label('No. PIC')->maxLength(30),
                    TextInput::make('pic_email')->label('Email PIC')->email()->maxLength(150),

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
                TextColumn::make('email')->label('Email')->toggleable(),
                TextColumn::make('phone')->label('Telepon')->toggleable(),
                TextColumn::make('pic_name')->label('PIC')->toggleable(),
                TextColumn::make('updated_at')->label('Diubah')->since()->sortable(),
            ])
            ->filters([
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
