<?php

namespace App\Filament\FC\Resources;

use App\Enums\MPCheckStatus;
use App\Filament\FC\Resources\BriefingSessionResource\Pages;
use App\Filament\FC\Resources\BriefingSessionResource\RelationManagers\AttendancesRelationManager;
use App\Filament\FC\Resources\BriefingSessionResource\RelationManagers\StockApdChecksRelationManager;
use App\Models\BriefingSession;
use App\Models\Depot;
use App\Models\Shipment;
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

    protected static ?int $navigationSort = 3;

    // Disembunyikan dari navigasi — akses melalui Monitoring Operasional
    protected static bool $shouldRegisterNavigation = false;

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
            ->withCount(['attendances', 'presentAttendances', 'shipments']);
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
                        ->live()
                        ->afterStateUpdated(function ($state, callable $set) {
                            // Auto-resolve coordinator from depot ownership.
                            $coordId = $state
                                ? Depot::where('id', $state)->value('coordinator_user_id')
                                : null;
                            $set('coordinator_user_id', $coordId);
                        }),

                    Placeholder::make('coordinator_display')
                        ->label('PIC (Koordinator)')
                        ->content(function (Get $get): string {
                            $depotId = $get('depot_id');
                            if (! $depotId) {
                                return '— (pilih depot terlebih dahulu)';
                            }
                            $name = Depot::with('coordinator:id,name')
                                ->where('id', $depotId)
                                ->first()
                                ?->coordinator?->name;
                            return $name ?? '— (belum ada koordinator)';
                        }),

                    \Filament\Forms\Components\Hidden::make('coordinator_user_id')
                        ->default(function () {
                            $user = Filament::auth()->user();
                            $depotId = $user?->scope_unit_type === 'depot'
                                ? $user->scope_unit_id
                                : Depot::where('coordinator_user_id', $user?->id)->value('id');
                            return $depotId
                                ? Depot::where('id', $depotId)->value('coordinator_user_id')
                                : $user?->id;
                        }),

                    TextInput::make('summary_headcount')
                        ->label('Kebutuhan Tim SOP')
                        ->numeric()
                        ->minValue(0)
                        ->default(5)
                        ->helperText('SOP minimum: 1 Koordinator + 4 Operator = 5 MP'),

                    Textarea::make('notes')
                        ->label('Catatan / Topik Briefing')
                        ->columnSpanFull()
                        ->rows(3),
                ]),

            Section::make('Shipment Kandidat')
                ->description('Pilih shipment yang akan dikerjakan pada sesi briefing ini.')
                ->schema([
                    Select::make('shipments')
                        ->label('Shipment')
                        ->multiple()
                        ->relationship(
                            name: 'shipments',
                            titleAttribute: 'code',
                            modifyQueryUsing: function (Builder $query, Get $get) {
                                $depotId = $get('depot_id');

                                // Select only non-JSON columns so PostgreSQL DISTINCT works.
                                // Filament's multi-select relationship adds DISTINCT internally;
                                // shipments has JSON columns (attachments, containers, etc.)
                                // that break DISTINCT on shipments.*.
                                return $query
                                    ->select([
                                        'shipments.id',
                                        'shipments.code',
                                        'shipments.customer_id',
                                        'shipments.status',
                                        'shipments.assigned_depot_id',
                                    ])
                                    ->readyForBriefing($depotId ?: null)
                                    ->orderBy('shipments.code');
                            }
                        )
                        ->getOptionLabelFromRecordUsing(fn (Shipment $r) =>
                            $r->code . ' — ' . ($r->customer?->name ?? '-')
                        )
                        ->searchable(['code'])
                        ->preload(false)
                        ->columnSpanFull(),
                ]),

            Section::make('Shipment Readiness')
                ->columns(4)
                ->visible(fn (?BriefingSession $record) => $record !== null)
                ->schema([
                    Placeholder::make('shipment_assigned')
                        ->label('Shipment Assigned')
                        ->content(fn (BriefingSession $record): string =>
                            (string) $record->shipments()->count()
                        ),

                    Placeholder::make('expected_unit')
                        ->label('Expected Unit')
                        ->content(fn (BriefingSession $record): string =>
                            $record->expected_unit . ' unit'
                        ),

                    Placeholder::make('actual_unit_masuk_yard')
                        ->label('Actual Unit Handover')
                        ->content(fn (BriefingSession $record): string =>
                            $record->actual_unit_masuk_yard . ' unit'
                        ),

                    Placeholder::make('unit_gap')
                        ->label('Gap (Expected − Actual)')
                        ->content(fn (BriefingSession $record): string =>
                            $record->unit_gap . ' unit'
                        ),
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
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),

                // ── Depot & PIC (toggleable — detail reference) ───────────
                TextColumn::make('depot.name')
                    ->label('Depot')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('coordinator.name')
                    ->label('PIC')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                // ── Shipment count — from withCount('shipments') in getEloquentQuery ──
                TextColumn::make('shipments_count')
                    ->label('Shipment')
                    ->alignCenter()
                    ->suffix(' SPPB')
                    ->sortable(false),

                // ── Expected Unit ─────────────────────────────────────────
                TextColumn::make('expected_unit_col')
                    ->label('Expected')
                    ->alignCenter()
                    ->suffix(' unit')
                    ->getStateUsing(fn ($record) => $record->expected_unit)
                    ->sortable(false),

                // ── Actual Unit Handover — derived post-cutoff, legacy pre-cutoff ──
                TextColumn::make('actual_unit_handover')
                    ->label('Actual Handover')
                    ->alignCenter()
                    ->suffix(' unit')
                    ->getStateUsing(fn ($record) => $record->actual_unit_masuk_yard)
                    ->sortable(false),

                // ── Gap Unit ──────────────────────────────────────────────
                TextColumn::make('unit_gap_col')
                    ->label('Gap')
                    ->alignCenter()
                    ->getStateUsing(fn ($record) => $record->unit_gap)
                    ->formatStateUsing(fn ($state) => $state > 0 ? "+{$state}" : (string) $state)
                    ->color(fn ($state) => $state <= 0 ? 'success' : 'warning')
                    ->badge()
                    ->sortable(false),

                // ── Need Tim SOP ───────────────────────────────────────────
                TextColumn::make('summary_headcount')
                    ->label('Need Tim')
                    ->alignCenter()
                    ->suffix(' MP')
                    ->sortable(),

                // ── MP Attend — from withCount('presentAttendances') ───────
                TextColumn::make('present_attendances_count')
                    ->label('MP Attend')
                    ->alignCenter()
                    ->suffix(' MP')
                    ->sortable(),

                // ── Gap = attend − need (computed in PHP, no query) ────────
                TextColumn::make('attendance_gap')
                    ->label('Gap')
                    ->state(function ($record) {
                        $attend = (int) ($record->present_attendances_count ?? 0);
                        $need   = (int) ($record->summary_headcount ?? 0);

                        return $attend - $need;
                    })
                    ->formatStateUsing(fn ($state) => $state > 0 ? "+{$state}" : (string) $state)
                    ->color(fn ($state) => match (true) {
                        $state > 0  => 'success',
                        $state === 0 => 'gray',
                        default     => 'danger',
                    })
                    ->weight(fn ($state) => $state < 0 ? 'bold' : null)
                    ->alignCenter()
                    ->sortable(false),

                // ── READY / NOT READY — FIT >= need AND mp_check cleared ──
                TextColumn::make('mp_readiness_status')
                    ->label('Kesiapan')
                    ->badge()
                    ->state(function ($record) {
                        $sufficient = (bool) $record->summary_sufficient;
                        $val        = $record->mp_check_status instanceof MPCheckStatus
                            ? $record->mp_check_status->value
                            : (string) $record->mp_check_status;
                        $cleared    = $val === 'cleared';

                        return ($sufficient && $cleared) ? 'ready' : 'not_ready';
                    })
                    ->formatStateUsing(fn ($state) => $state === 'ready' ? 'READY' : 'NOT READY')
                    ->color(fn ($state) => $state === 'ready' ? 'success' : 'danger')
                    ->alignCenter()
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
            StockApdChecksRelationManager::class,
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
