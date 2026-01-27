<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BriefingSessionResource\Pages;
use App\Filament\Resources\BriefingSessionResource\RelationManagers\AttendanceRelationManager;
use App\Models\BriefingSession;
use App\Models\Depot;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Facades\Filament;
use Filament\Tables\Columns\ViewColumn;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class BriefingSessionResource extends Resource
{
    protected static ?string $model = BriefingSession::class;

    protected static ?string $navigationGroup = 'Transaksi';
    protected static ?string $navigationLabel = 'Briefing MP';
    protected static ?string $pluralLabel     = 'Briefing MP';
    protected static ?string $modelLabel      = 'Sesi Briefing';
    protected static ?string $navigationIcon  = 'heroicon-m-clipboard-document-check';
    protected static ?int    $navigationSort  = 20;

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            DatePicker::make('date')
                ->label('Tanggal')
                ->default(now())
                ->closeOnDateSelection()
                ->required()
                ->rule(function (Get $get, ?BriefingSession $record) {
                    $depotId = (int) $get('depot_id');
                    if (! $depotId) return null;
                    return Rule::unique('briefing_sessions', 'date')
                        ->where(fn($query) => $query->where('depot_id', $depotId))
                        ->ignore($record?->id);
                }),

            Select::make('depot_id')
                ->relationship('depot', 'name', function (EloquentBuilder $query) {
                    $u = Filament::auth()?->user();

                    $isFc = false;
                    if ($u) {
                        $isFc = DB::table('model_has_roles')
                            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                            ->where('model_has_roles.model_type', User::class)
                            ->where('model_has_roles.model_id', $u->id)
                            ->where('roles.name', 'field_coordinator')
                            ->exists();
                    }

                    if ($u && $isFc && isset($u->branch_id)) {
                        $query->where('branch_id', $u->branch_id);
                    }

                    $query->orderBy('name');
                })
                ->label('Depot')
                ->searchable()
                ->preload()
                ->required()
                ->reactive(),

            Select::make('coordinator_user_id')
                ->label('PIC')
                ->options(function (Get $get) {
                    $depotId  = $get('depot_id');
                    $branchId = $depotId ? Depot::query()->whereKey($depotId)->value('branch_id') : null;
                    return User::role('field_coordinator')
                        ->when($branchId, fn($query) => $query->where('branch_id', $branchId))
                        ->orderBy('name')
                        ->pluck('name', 'id');
                })
                ->searchable()
                ->preload()
                ->required()
                ->rule(function (Get $get) {
                    return function (string $attribute, $value, \Closure $fail) use ($get) {
                        if (! $value) return;
                        $depotId  = (int) $get('depot_id');
                        $branchId = $depotId ? Depot::query()->whereKey($depotId)->value('branch_id') : null;
                        $ok = User::role('field_coordinator')
                            ->when($branchId, fn($query) => $query->where('branch_id', $branchId))
                            ->whereKey($value)
                            ->exists();
                        if (! $ok) $fail('PIC harus koordinator lapangan di cabang yang sama.');
                    };
                }),

            TextInput::make('summary_headcount')
                ->label('Jumlah MP')
                ->numeric()
                ->minValue(0)
                ->default(0),

            Placeholder::make('computed_sufficient')
                ->label('Kecukupan')
                ->content(function (?BriefingSession $record) {
                    if (! $record?->id) return '—';
                    $present = $record->presentAttendances()->count();
                    $target  = (int) $record->summary_headcount;
                    if ($target <= 0) return 'Target belum diisi';
                    return $present >= $target
                        ? "Cukup ({$present}/{$target})"
                        : "Tidak Cukup ({$present}/{$target})";
                })
                ->extraAttributes(function (?BriefingSession $record) {
                    if (! $record?->id || ! $record->summary_headcount) return ['class' => 'text-gray-500'];
                    $present = $record->presentAttendances()->count();
                    $target  = (int) $record->summary_headcount;
                    return ['class' => $present >= $target ? 'text-green-600 font-medium' : 'text-rose-600 font-medium'];
                }),

            Textarea::make('summary_solution')
                ->label('Solusi/Keterangan')
                ->columnSpanFull()
                ->required(function (Get $get, ?BriefingSession $record) {
                    $target  = (int) $get('summary_headcount');
                    if (! $record?->id || $target <= 0) return false;
                    $present = $record->presentAttendances()->count();
                    return $present < $target;
                }),

            Textarea::make('notes')
                ->label('Catatan')
                ->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->query(function (): EloquentBuilder {
                return static::getEloquentQuery();
            })
            ->defaultSort('date', 'desc')
            ->columns([
                TextColumn::make('date')->date()->label('Tanggal')->sortable(),
                TextColumn::make('depot.name')->label('Depot')->sortable()->toggleable(),
                TextColumn::make('coordinator.name')->label('Koordinator')->sortable()->toggleable(),
                TextColumn::make('summary_headcount')->label('Target MP')->sortable()->toggleable(),

                ViewColumn::make('attendance_progress')
                    ->label('Hadir')
                    ->state(function ($record) {
                        $present = (int) ($record->present_attendances_count ?? $record->presentAttendances()->count());
                        $target  = max(0, (int) $record->summary_headcount);

                        $percent = $target > 0 ? (int) round(min(100, ($present / $target) * 100)) : 0;

                        $tone = 'gray';
                        if ($target > 0) {
                            $tone = $present >= $target ? 'emerald'
                                : ($present >= ceil($target * 0.5) ? 'amber' : 'rose');
                        }

                        return [
                            'present' => $present,
                            'target'  => $target,
                            'percent' => $percent,
                            'tone'    => $tone,
                        ];
                    })
                    ->view('tables.columns.attendance-progress')
                    ->sortable(false)
                    ->toggleable(),

                TextColumn::make('present_attendances_count')
                    ->label('Hadir (x/y)')
                    ->getStateUsing(fn($record) => $record->summary_headcount
                        ? "{$record->present_attendances_count}/{$record->summary_headcount}"
                        : (string) $record->present_attendances_count)
                    ->sortable()
                    ->extraAttributes(['class' => 'font-medium'])
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('summary_sufficient')->label('Kecukupan')
                    ->state(function ($record) {
                        $present = $record->present_attendances_count ?? $record->presentAttendances()->count();
                        $target  = (int) $record->summary_headcount;
                        if ($target <= 0) return 'Belum Ditentukan';
                        return $present >= $target ? 'Cukup' : 'Tidak Cukup';
                    })
                    ->badge()
                    ->color(fn($record) => ($record->summary_headcount > 0 && ($record->present_attendances_count ?? 0) < $record->summary_headcount) ? 'danger' : 'success')
                    ->tooltip(fn($record) => "Hadir " . ($record->present_attendances_count ?? 0) . " / Target " . (int) $record->summary_headcount),
            ])
            ->filters([
                Filter::make('today')->label('Hari ini')
                    ->query(fn(EloquentBuilder $query) => $query->whereDate('date', now()->toDateString())),
                Filter::make('bulan_ini')->label('Bulan ini')
                    ->query(fn(EloquentBuilder $query) => $query->whereBetween('date', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])),
                Filter::make('rentang')
                    ->form([DatePicker::make('from')->label('Dari'), DatePicker::make('until')->label('Sampai')])
                    ->query(function (EloquentBuilder $query, array $data) {
                        if ($data['from'] ?? null)  $query->whereDate('date', '>=', $data['from']);
                        if ($data['until'] ?? null) $query->whereDate('date', '<=', $data['until']);
                    }),
                SelectFilter::make('depot_id')->label('Depot')->relationship('depot', 'name'),
                SelectFilter::make('coordinator_user_id')->label('Koordinator')->relationship('coordinator', 'name'),
            ])
            ->headerActions([
                Tables\Actions\Action::make('startToday')
                    ->label('Mulai Briefing Hari Ini')
                    ->icon('heroicon-m-play')
                    ->action(function () {
                        $u = Filament::auth()?->user();

                        $isFc = false;
                        if ($u) {
                            $isFc = DB::table('model_has_roles')
                                ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                                ->where('model_has_roles.model_type', User::class)
                                ->where('model_has_roles.model_id', $u->id)
                                ->where('roles.name', 'field_coordinator')
                                ->exists();
                        }

                        $depotId = Depot::query()
                            ->when(
                                $u && $isFc && isset($u->branch_id),
                                fn($query) => $query->where('branch_id', $u->branch_id)
                            )
                            ->orderBy('name')
                            ->value('id');

                        $session = BriefingSession::firstOrCreate(
                            ['date' => now()->toDateString(), 'depot_id' => $depotId],
                            ['coordinator_user_id' => $u?->id, 'summary_headcount' => 0]
                        );

                        return redirect(static::getUrl('edit', ['record' => $session]));
                    }),
                Tables\Actions\CreateAction::make()->label('Tambah Sesi'),
            ])
            ->actions([
                Tables\Actions\Action::make('kelola')
                    ->label('Kelola')
                    ->icon('heroicon-m-clipboard-document-list')
                    ->url(fn($record) => static::getUrl('edit', ['record' => $record])),
                Tables\Actions\EditAction::make()->label('Ubah'),
                Tables\Actions\DeleteAction::make()->label('Hapus'),
            ])
            ->bulkActions([Tables\Actions\DeleteBulkAction::make()->label('Hapus')]);
    }

    public static function getEloquentQuery(): EloquentBuilder
    {
        return static::getModel()::query()
            ->with(['depot:id,name', 'coordinator:id,name'])
            ->withCount(['attendances', 'presentAttendances']);
    }

    public static function getRelations(): array
    {
        return [
            AttendanceRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBriefingSessions::route('/'),
            'create' => Pages\CreateBriefingSession::route('/create'),
            'edit' => Pages\EditBriefingSession::route('/{record}/edit'),
        ];
    }
}
