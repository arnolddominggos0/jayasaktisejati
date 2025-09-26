<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BriefingSessionResource\Pages;
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
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Validation\Rule;

class BriefingSessionResource extends Resource
{
    protected static ?string $model = BriefingSession::class;

    protected static ?string $navigationGroup = 'Manajemen MP';
    protected static ?string $navigationLabel = 'Sesi Briefing';
    protected static ?string $pluralLabel     = 'Sesi Briefing';
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
                        ->where(fn($q) => $q->where('depot_id', $depotId))
                        ->ignore($record?->id);
                }),

            Select::make('depot_id')
                ->relationship('depot', 'name')
                ->label('Depot')
                ->searchable()
                ->preload()
                ->required()
                ->reactive(),

            Select::make('coordinator_user_id')
                ->label('Koordinator (PIC)')
                ->options(function (Get $get) {
                    $depotId  = $get('depot_id');
                    $branchId = $depotId ? Depot::query()->whereKey($depotId)->value('branch_id') : null;

                    return User::role('field_coordinator')
                        ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
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
                            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
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
            ->query(fn(): EloquentBuilder => static::getEloquentQuery())
            ->defaultSort('date', 'desc')
            ->columns([
                TextColumn::make('date')->date()->label('Tanggal')->sortable(),
                TextColumn::make('depot.name')->label('Depot')->sortable()->toggleable(),
                TextColumn::make('coordinator.name')->label('Koordinator')->sortable()->toggleable(),
                TextColumn::make('attendances_count')
                    ->label('Absensi (total)')
                    ->sortable()
                    ->getStateUsing(fn($record) => $record->attendances_count),

                TextColumn::make('summary_sufficient')
                    ->label('Kecukupan')
                    ->state(function ($record) {
                        $present = $record->present_attendances_count ?? $record->presentAttendances()->count();
                        $target  = (int) $record->summary_headcount;
                        if ($target <= 0) return 'Belum Ditentukan';
                        return $present >= $target ? 'Cukup' : 'Tidak Cukup';
                    })
                    ->badge()
                    ->color(function ($record) {
                        $present = $record->present_attendances_count ?? $record->presentAttendances()->count();
                        $target  = (int) $record->summary_headcount;
                        if ($target <= 0) return 'gray';
                        return $present >= $target ? 'success' : 'danger';
                    })
                    ->tooltip(function ($record) {
                        $present = $record->present_attendances_count ?? $record->presentAttendances()->count();
                        $target  = (int) $record->summary_headcount;
                        return "Hadir {$present} / Target {$target}";
                    }),

                TextColumn::make('present_attendances_count')
                    ->label('Hadir')
                    ->sortable()
                    ->getStateUsing(fn($record) => $record->summary_headcount
                        ? "{$record->present_attendances_count}/{$record->summary_headcount}"
                        : (string) ($record->present_attendances_count))
                    ->extraAttributes(['class' => 'font-medium']),
            ])
            ->filters([
                Filter::make('bulan_ini')
                    ->label('Bulan ini')
                    ->query(fn(EloquentBuilder $q) => $q->whereBetween('date', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])),
                Filter::make('rentang_tanggal')
                    ->form([
                        DatePicker::make('from')->label('Dari'),
                        DatePicker::make('until')->label('Sampai'),
                    ])
                    ->query(function (EloquentBuilder $q, array $data) {
                        if ($data['from'] ?? null) $q->whereDate('date', '>=', $data['from']);
                        if ($data['until'] ?? null) $q->whereDate('date', '<=', $data['until']);
                        return $q;
                    }),
                SelectFilter::make('depot_id')->label('Depot')->relationship('depot', 'name'),
                SelectFilter::make('coordinator_user_id')->label('Koordinator')->relationship('coordinator', 'name'),
            ])
            ->actions([
                Tables\Actions\Action::make('kelolaAbsensi')
                    ->label('Kelola Absensi')
                    ->icon('heroicon-m-clipboard-document-list')
                    ->url(fn($record) => route('filament.admin.resources.briefing-attendances.index', ['session_id' => $record->id]))
                    ->color(fn($record) => ($record->present_attendances_count ?? 0) < (int) $record->summary_headcount ? 'danger' : 'gray'),
                Tables\Actions\EditAction::make()->label('Ubah'),
                Tables\Actions\DeleteAction::make()->label('Hapus'),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()->label('Hapus'),
            ]);
    }

    public static function getEloquentQuery(): EloquentBuilder
    {
        return static::getModel()::query()
            ->with(['depot:id,name', 'coordinator:id,name'])
            ->withCount(['attendances', 'presentAttendances']);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListBriefingSessions::route('/'),
            'create' => Pages\CreateBriefingSession::route('/create'),
            'edit'   => Pages\EditBriefingSession::route('/{record}/edit'),
        ];
    }
}
