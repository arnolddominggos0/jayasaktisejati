<?php

namespace App\Filament\FC\Resources;

use App\Enums\MPCheckStatus;
use App\Filament\FC\Resources\BriefingSessionResource\Pages;
use App\Filament\FC\Resources\BriefingSessionResource\RelationManagers\AttendancesRelationManager;
use App\Models\BriefingSession;
use App\Models\Depot;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
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
            $query->whereHas('depot', fn ($q) => $q->where('branch_id', $branchId));
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
                        ->live(),

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
                        ->default(fn () => Filament::auth()->user()?->id),

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
                ->columns(3)
                ->schema([
                    Placeholder::make('mp_check_status_display')
                        ->label('Status')
                        ->content(fn (?BriefingSession $record) => $record?->mp_check_status ? MPCheckStatus::from($record->mp_check_status)->label() : 'Draft')
                        ->extraAttributes(fn (?BriefingSession $record) => [
                            'class' => $record?->mp_check_status === MPCheckStatus::Approved->value
                                ? 'text-green-600 font-bold'
                                : 'text-gray-600 font-medium',
                        ]),

                    Placeholder::make('approver_display')
                        ->label('Disetujui Oleh')
                        ->content(fn (?BriefingSession $record) => $record?->approved_by ? User::find($record->approved_by)?->name : '-'),

                    Placeholder::make('approved_at_display')
                        ->label('Waktu Approve')
                        ->content(fn (?BriefingSession $record) => $record?->approved_at ? $record->approved_at->format('d M Y H:i') : '-'),
                ])
                ->visible(fn (?BriefingSession $record) => $record !== null),
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
                    ->toggleable(),

                TextColumn::make('coordinator.name')
                    ->label('PIC')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('summary_headcount')
                    ->label('Target MP')
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
                    ->label('Status MP')
                    ->badge()
                    ->formatStateUsing(fn ($state) => MPCheckStatus::tryFrom($state)?->label() ?? $state)
                    ->color(fn ($state) => MPCheckStatus::tryFrom($state)?->color() ?? 'gray')
                    ->sortable(),
            ])
            ->filters([
                Filter::make('today')
                    ->label('Hari Ini')
                    ->query(fn (Builder $query) => $query->whereDate('date', now()->toDateString())),

                Filter::make('this_week')
                    ->label('Minggu Ini')
                    ->query(fn (Builder $query) => $query->whereBetween('date', [now()->startOfWeek()->toDateString(), now()->endOfWeek()->toDateString()])),

                Filter::make('this_month')
                    ->label('Bulan Ini')
                    ->query(fn (Builder $query) => $query->whereBetween('date', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])),

                SelectFilter::make('depot_id')
                    ->label('Depot')
                    ->relationship('depot', 'name'),

                SelectFilter::make('mp_check_status')
                    ->label('Status MP Check')
                    ->options(collect(MPCheckStatus::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()])),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Approve Briefing Session')
                    ->modalDescription('Setujui sesi briefing ini? Ini akan membuka gate untuk loading.')
                    ->visible(fn (BriefingSession $record) => in_array($record->mp_check_status, [
                        MPCheckStatus::Draft->value,
                        MPCheckStatus::OnCheck->value,
                        MPCheckStatus::WaitingAction->value,
                    ]))
                    ->action(function (BriefingSession $record) {
                        $record->update([
                            'mp_check_status' => MPCheckStatus::Approved->value,
                            'approved_at' => now(),
                            'approved_by' => Filament::auth()->user()?->id,
                        ]);

                        $record->refreshSufficientFlag();

                        Notification::make()
                            ->title('Briefing Disetujui')
                            ->success()
                            ->send();
                    }),

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
