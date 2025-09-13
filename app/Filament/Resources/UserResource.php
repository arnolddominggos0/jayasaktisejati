<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Tables;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationLabel = 'User';
    protected static ?string $pluralModelLabel = 'User';
    protected static ?string $modelLabel = 'User';
    protected static ?string $navigationGroup = 'Manajemen Data';
    protected static ?int $navigationSort = 5;

    public static function canViewAny(): bool
    {
        return auth_user()?->hasAnyRole('super_admin', 'office_admin') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Group::make()->schema([
                Forms\Components\Section::make('Data User')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama Lengkap')
                            ->required()
                            ->maxLength(150)
                            ->placeholder('Nama User'),

                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->unique(ignoreRecord: true)
                            ->required()
                            ->placeholder('user@example.com'),

                        TextInput::make('password')
                            ->label('Password (kosongkan jika tidak diubah)')
                            ->password()
                            ->revealable()
                            ->dehydrateStateUsing(fn($state) => filled($state) ? Hash::make($state) : null)
                            ->dehydrated(fn($state) => filled($state))
                            ->maxLength(100),
                    ])->columns(2),

                Section::make('Atribusi')
                    ->schema([
                        Section::make('branch_id')
                            ->label('Cabang')
                            ->placeholder('Pilih cabang')
                            ->options(
                                \App\Models\Branch::query()
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                            )
                            ->searchable(),

                        Section::make('customer_id')
                            ->label('Customer (opsional, untuk akun portal)')
                            ->placeholder('Pilih customer')
                            ->options(
                                \App\Models\Customer::query()
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                            )
                            ->searchable()
                            ->helperText('Isi jika user ini adalah akun portal milik customer tertentu.'),
                    ])->columns(2),

                Forms\Components\Section::make('Role & Izin')
                    ->schema([
                        Section::make('roles')
                            ->label('Role')
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->options(fn() => Role::query()->orderBy('name')->pluck('name', 'name'))
                            ->helperText('Pilih satu atau lebih role: super_admin, office_admin, field_coordinator, customer.')
                            ->required(),
                    ]),
            ])->columnSpan(['lg' => 2]),
        ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Nama')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('email')->label('Email')->searchable()->copyable(),
                Tables\Columns\TextColumn::make('branch.name')->label('Cabang')->toggleable(),
                Tables\Columns\TextColumn::make('customer.name')->label('Customer')->toggleable(),
                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Role')
                    ->badge()
                    ->separator(', ')
                    ->sortable()
                    ->formatStateUsing(fn($state) => is_array($state) ? implode(', ', $state) : $state),
                Tables\Columns\TextColumn::make('updated_at')->label('Diubah')->since()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('roles')
                    ->label('Filter Role')
                    ->multiple()
                    ->options(fn() => Role::query()->orderBy('name')->pluck('name', 'name')),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('Ubah'),
                Tables\Actions\DeleteAction::make()->label('Hapus')
                    ->visible(
                        fn($record) => (auth_user()?->hasRole('super_admin') ?? false)
                            && ! $record->hasRole('super_admin')
                            && ($record->id !== auth_user()?->id)
                    ),
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
            'index'  => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit'   => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) User::count();
    }
}
