<?php

namespace App\Filament\FC\Resources;

use App\Enums\MPCheckStatus;
use App\Filament\FC\Resources\BriefingSessionResource\Pages;
use App\Filament\FC\Resources\BriefingSessionResource\RelationManagers\AttendancesRelationManager;
use App\Models\BriefingSession;
use App\Models\Depot;
use Filament\Facades\Filament;
use Filament\Forms\Get;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BriefingSessionResource extends Resource
{
    protected static ?string $model = BriefingSession::class;

    protected static ?string $navigationGroup = 'Operasional Lapangan';

    protected static ?string $navigationLabel = 'Briefing Harian';

    protected static ?string $pluralLabel = 'Briefing Harian';

    protected static ?string $modelLabel = 'Sesi Briefing';

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?int $navigationSort = 5;

    public static function canViewAny(): bool
    {
        return Filament::auth()->user()?->hasRole('field_coordinator') ?? false;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        $user = Filament::auth()->user();
        if (! $user) {
            return $query->whereRaw('1=0');
        }

        $branchId = app()->bound('scope.branch_id')
            ? app('scope.branch_id')
            : ($user->effectiveBranchId() ?? null);

        if ($branchId) {
            $query->whereHas('depot', fn($q) => $q->where('branch_id', $branchId));
        }

        $depotId = app()->bound('scope.depot_id')
            ? app('scope.depot_id')
            : ($user->scope_unit_type === 'depot' ? $user->scope_unit_id : Depot::where('coordinator_user_id', $user->id)->value('id'));

        if ($depotId) {
            $query->where('depot_id', $depotId);
        }

        return $query->with(['depot:id,name', 'coordinator:id,name'])
            ->withCount(['attendances', 'presentAttendances']);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Informasi Briefing')
                ->columns(2)
                ->schema([
                    DatePicker::make('date')
                        ->label('Tanggal')
                        ->default(now())
                        ->closeOnDateSelection()
                        ->required()
                        ->live()
                        ->rules([
                            // Prevent duplicate briefings: one per depot per day.
                            fn(Get $get, ?BriefingSession $record): \Closure => function (
                                string $attribute,
                                mixed  $value,
                                \Closure $fail,
                            ) use ($get, $record): void {
                                $depotId = $get('depot_id');
                                if (! $value || ! $depotId) {
                                    return;
                                }
                                $exists = BriefingSession::whereDate('date', $value)
                                    ->where('depot_id', $depotId)
                                    ->when($record?->id, fn($q, $id) => $q->where('id', '!=', $id))
                                    ->exists();
                                if ($exists) {
                                    $fail('Briefing untuk tanggal ini di depot yang dipilih sudah ada.');
                                }
                            },
                        ]),

                    Select::make('depot_id')
                        ->label('Depot')
                        ->relationship('depot', 'name')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->default(function () {
                            $user = Filament::auth()->user();

                            return $user?->scope_unit_type === 'depot'
                                ? $user->scope_unit_id
                                : Depot::where('coordinator_user_id', $user?->id)->value('id');
                        })
                        ->live(),

                    Select::make('coordinator_user_id')
                        ->label('PIC (Koordinator)')
                        ->relationship('coordinator', 'name')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->default(fn() => Filament::auth()->user()?->id),

                    TextInput::make('summary_headcount')
                        ->label('Target Jumlah MP')
                        ->numeric()
                        ->minValue(0)
                        ->default(8)
                        ->helperText('Target jumlah manpower yang harus hadir'),

                    Textarea::make('notes')
                        ->label('Catatan / Topik Briefing')
                        ->columnSpanFull()
                        ->rows(3),
                ]),

            Section::make('Status MP Check')
                ->columns(1)
                ->schema([
                    Placeholder::make('mp_check_status_display')
                        ->label('Status')
                        ->content(function (?BriefingSession $record): string {
                            if (! $record?->mp_check_status) {
                                return 'Draft';
                            }
                            $enum = $record->mp_check_status instanceof MPCheckStatus
                                ? $record->mp_check_status
                                : MPCheckStatus::tryFrom((string) $record->mp_check_status);
                            return $enum?->label() ?? 'Draft';
                        }),
                ])
                ->visible(fn(?BriefingSession $record) => $record !== null),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('date', 'desc')
            ->columns([
                TextColumn::make('date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('depot.name')
                    ->label('Depot')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('coordinator.name')
                    ->label('PIC')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('summary_headcount')
                    ->label('Target MP')
                    ->alignCenter()
                    ->sortable(),

                ViewColumn::make('attendance_progress')
                    ->label('Kehadiran')
                    ->state(function ($record) {
                        $present = (int) ($record->present_attendances_count ?? $record->presentAttendances()->count());
                        $target = max(0, (int) $record->summary_headcount);
                        $percent = $target > 0 ? (int) round(min(100, ($present / $target) * 100)) : 0;
                        $tone = $target > 0 ? ($present >= $target ? 'emerald' : ($present >= ceil($target * 0.5) ? 'amber' : 'rose')) : 'gray';

                        return ['present' => $present, 'target' => $target, 'percent' => $percent, 'tone' => $tone];
                    })
                    ->view('tables.columns.attendance-progress')
                    ->sortable(false),

                TextColumn::make('mp_check_status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(function ($state): string {
                        $val = $state instanceof MPCheckStatus ? $state : MPCheckStatus::tryFrom((string) $state);
                        return $val?->label() ?? (string) $state;
                    })
                    ->color(function ($state): string {
                        $val = $state instanceof MPCheckStatus ? $state->value : (string) $state;
                        return match ($val) {
                            'cleared'             => 'success',
                            'on_check'            => 'warning',
                            'waiting_action',
                            'failed'              => 'danger',
                            default               => 'gray',
                        };
                    })
                    ->sortable(),
            ])
            ->emptyStateIcon('heroicon-o-clipboard-document-check')
            ->emptyStateHeading('Belum ada briefing')
            ->emptyStateDescription('Buat briefing harian untuk memulai operasional hari ini.')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Mulai Briefing')
                    ->icon('heroicon-m-plus'),
            ])
            ->filters([
                Filter::make('today')
                    ->label('Hari Ini')
                    ->query(fn(Builder $query) => $query->whereDate('date', now()->toDateString())),

                Filter::make('this_week')
                    ->label('Minggu Ini')
                    ->query(fn(Builder $query) => $query->whereBetween('date', [now()->startOfWeek()->toDateString(), now()->endOfWeek()->toDateString()])),

                Filter::make('this_month')
                    ->label('Bulan Ini')
                    ->query(fn(Builder $query) => $query->whereBetween('date', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])),

                SelectFilter::make('depot_id')
                    ->label('Depot')
                    ->relationship('depot', 'name'),

                SelectFilter::make('mp_check_status')
                    ->label('Status MP Check')
                    ->options(collect(MPCheckStatus::cases())->mapWithKeys(fn($c) => [$c->value => $c->label()])),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Lihat')
                    ->icon('heroicon-o-eye'),

                Tables\Actions\EditAction::make()
                    ->label('Ubah'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Hapus Terpilih'),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            AttendancesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBriefingSessions::route('/'),
            'create' => Pages\CreateBriefingSession::route('/create'),
            'view' => Pages\ViewBriefingSession::route('/{record}'),
            'edit' => Pages\EditBriefingSession::route('/{record}/edit'),
        ];
    }
}
