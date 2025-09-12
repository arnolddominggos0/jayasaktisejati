<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerResource\Pages;
use App\Models\Customer;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationLabel = 'Customer';
    protected static ?string $pluralModelLabel = 'Customer';
    protected static ?string $modelLabel = 'Customer';
    protected static ?string $navigationGroup = 'Manajemen Data';
    protected static ?int $navigationSort = 10;

    public static function canViewAny(): bool
    {
        $u = auth()->user();
        return $u?->hasAnyRole(['super_admin','office_admin']);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Group::make()
                ->schema([
                    Section::make('Data Customer')
                        ->schema([
                            TextInput::make('code')
                                ->label('Kode Customer')
                                ->placeholder('CTM-0001')
                                ->required()
                                ->unique(ignoreRecord: true)
                                ->maxLength(20),

                            TextInput::make('name')
                                ->label('Nama Customer / Perusahaan')
                                ->placeholder('PT Contoh Sejahtera')
                                ->required()
                                ->maxLength(150),

                            TextInput::make('email')
                                ->label('Email')
                                ->email()
                                ->placeholder('customer@example.com')
                                ->maxLength(150),

                            TextInput::make('phone')
                                ->label('No. Telepon')
                                ->placeholder('0812xxxxxxx')
                                ->maxLength(30),

                            TextInput::make('npwp')
                                ->label('NPWP')
                                ->maxLength(32),
                        ])->columns(2),

                    Section::make('Kontak & Alamat')
                        ->schema([
                            TextInput::make('pic_name')
                                ->label('Nama PIC')
                                ->maxLength(100),

                            TextInput::make('pic_phone')
                                ->label('No. PIC')
                                ->maxLength(30),

                            Textarea::make('address')
                                ->label('Alamat')
                                ->rows(3)
                                ->columnSpanFull(),

                            TextInput::make('branch_id')
                                ->label('Cabang (Opsional)')
                                ->placeholder('ID Cabang, jika ada')
                                ->numeric()
                                ->minValue(1),
                        ])->columns(2),
                ])->columnSpan(['lg' => 2]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->label('Kode')->badge()->searchable()->sortable()->copyable(),
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
