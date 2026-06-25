<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Depot;
use App\Models\User;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Pengguna';

    protected static ?string $pluralModelLabel = 'Pengguna';

    protected static ?string $modelLabel = 'Pengguna';

    protected static ?string $navigationGroup = 'Master Data';

    protected static ?int $navigationSort = 6;

    public static function canViewAny(): bool
    {
        return auth_user()?->isSuperAdmin() ?? false;
    }

    public static function getEloquentQuery(): Builder
    {
        $q = parent::getEloquentQuery();
        $u = auth_user();

        if ($u?->isSuperAdmin()) {
            return $q;
        }

        return $q->whereRaw('1=0');
    }

    public static function form(Form $form): Form
    {
        $isSuper = auth_user()?->isSuperAdmin() ?? false;

        return $form
            ->schema([
                Group::make()
                    ->schema([
                        Section::make('Data User')
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
                                    ->label(fn (?User $record) => $record
                                        ? 'Password (kosongkan jika tidak diubah)'
                                        : 'Password')
                                    ->helperText(fn (?User $record) => $record
                                        ? 'Biarkan kosong jika tidak mengganti password.'
                                        : 'Minimal 8 karakter.')
                                    ->password()
                                    ->revealable()
                                    ->required(fn (?User $record) => $record === null)
                                    ->minLength(8)
    				    ->dehydrateStateUsing(fn (?string $state) => filled($state) ? Hash::make(trim($state)) : null)
    				    ->dehydrated(fn (?string $state) => filled($state))
    				    ->maxLength(100)
                            ])
                            ->columns(2),

                        Section::make('Atribusi')
                            ->schema([
                                Select::make('branch_id')
                                    ->label('Cabang')
                                    ->placeholder('Pilih cabang')
                                    ->options(
                                        fn () => $isSuper
                                            ? Branch::query()->orderBy('name')->pluck('name', 'id')
                                            : Branch::query()->whereKey(auth_user()?->branch_id)->pluck('name', 'id')
                                    )
                                    ->default(fn () => auth_user()?->branch_id)
                                    ->searchable()
                                    ->required(),

                                Select::make('customer_id')
                                    ->label('Customer (untuk akun portal)')
                                    ->placeholder('Pilih customer')
                                    ->options(
                                        Customer::query()
                                            ->orderBy('name')
                                            ->pluck('name', 'id')
                                    )
                                    ->searchable()
                                    ->nullable()
                                    ->visible(fn (Get $get) => $get('role_name') === 'customer')
                                    ->required(fn (Get $get) => $get('role_name') === 'customer')
                                    ->helperText('Wajib jika role = customer.'),
                            ])
                            ->columns(2),

                        Section::make('Role')
                            ->schema([
                                Select::make('role_name')
                                    ->label('Role')
                                    ->options(
                                        fn () => Role::query()->orderBy('name')->pluck('name', 'name')
                                    )
                                    ->searchable()
                                    ->required()
                                    ->dehydrated(false) // tidak disimpan ke kolom users
                                    ->helperText('Hanya satu role per pengguna.')
                                    ->afterStateHydrated(function (Select $component, ?User $record) {
                                        if ($record) {
                                            $component->state($record->getRoleNames()->first());
                                        }
                                    }),
                            ]),
                    ])
                    ->columnSpan(['lg' => 2]),
            ])
            ->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nama')->searchable()->sortable(),
                TextColumn::make('email')->label('Email')->searchable()->copyable(),
                TextColumn::make('branch.name')->label('Cabang')->toggleable(),
                TextColumn::make('roles.name')
                    ->label('Role')
                    ->badge()
                    ->separator(', ')
                    ->sortable(false)
                    ->formatStateUsing(fn ($state) => is_array($state) ? implode(', ', $state) : (string) $state),

                TextColumn::make('fc_scope_status')
                    ->label('Scope FC')
                    ->getStateUsing(function (User $record): string {
                        if (! $record->isFieldCoordinator()) {
                            return '—';
                        }

                        $scopeComplete = $record->scope_unit_id
                            && $record->scope_branch_id
                            && $record->scope_unit_type;

                        if ($scopeComplete) {
                            // Verify live depot assignment still matches
                            $liveDepot = Depot::where('coordinator_user_id', $record->id)->first();
                            if (! $liveDepot) {
                                return '⚠ Tidak ada depot';
                            }
                            $mismatch = $record->scope_unit_id !== $liveDepot->id
                                || $record->scope_branch_id !== $liveDepot->branch_id
                                || $record->scope_unit_type !== 'depot';
                            return $mismatch ? '⚠ Scope mismatch' : '✓ Lengkap';
                        }

                        // Scope fields NULL — check if depot exists as fallback
                        $liveDepot = Depot::where('coordinator_user_id', $record->id)->first();
                        if (! $liveDepot) {
                            return '⚠ Tidak ada depot';
                        }

                        return '⚠ Scope tidak diisi';
                    })
                    ->badge()
                    ->color(function (User $record): string {
                        if (! $record->isFieldCoordinator()) {
                            return 'gray';
                        }

                        $scopeComplete = $record->scope_unit_id
                            && $record->scope_branch_id
                            && $record->scope_unit_type;

                        if ($scopeComplete) {
                            $liveDepot = Depot::where('coordinator_user_id', $record->id)->first();
                            if (! $liveDepot) return 'danger';
                            $mismatch = $record->scope_unit_id !== $liveDepot->id
                                || $record->scope_branch_id !== $liveDepot->branch_id
                                || $record->scope_unit_type !== 'depot';
                            return $mismatch ? 'danger' : 'success';
                        }

                        $liveDepot = Depot::where('coordinator_user_id', $record->id)->first();
                        return $liveDepot ? 'warning' : 'danger';
                    })
                    ->tooltip(function (User $record): ?string {
                        if (! $record->isFieldCoordinator()) {
                            return null;
                        }
                        if (! $record->scope_unit_id) {
                            return 'scope_branch_id, scope_unit_id, scope_unit_type belum diisi. '
                                . 'Middleware menggunakan fallback depot langsung. '
                                . 'Isi scope fields di form edit user untuk konfigurasi kanonik.';
                        }
                        return null;
                    })
                    ->toggleable(),

                TextColumn::make('updated_at')->label('Diubah')->since()->sortable(),
            ])
            ->filters([
                SelectFilter::make('roles')
                    ->label('Filter Role')
                    ->multiple()
                    ->relationship('roles', 'name'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('Ubah'),
                Tables\Actions\DeleteAction::make()
                    ->label('Hapus')
                    ->visible(fn ($record) => (auth_user()?->isSuperAdmin() ?? false)
                        && ! $record->isSuperAdmin()
                        && ($record->id !== auth_user()?->id)),
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) User::query()->count();
    }
}
