<?php

namespace App\Filament\Resources;

use App\Enums\AttendanceStatus;
use App\Filament\Resources\BriefingAttendanceResource\Pages;
use App\Models\BriefingAttendance;
use App\Models\BriefingSession;
use App\Models\Manpower;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Section;
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

class BriefingAttendanceResource extends Resource
{
    protected static ?string $model = BriefingAttendance::class;

    protected static ?string $navigationGroup = 'Manajemen MP';
    protected static ?string $navigationLabel = 'Absensi Briefing';
    protected static ?string $pluralLabel     = 'Absensi Briefing';
    protected static ?string $modelLabel      = 'Absensi Briefing';
    protected static ?string $navigationIcon  = 'heroicon-m-clipboard-document-list';
    protected static ?int    $navigationSort  = 30;

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function form(Forms\Form $form): Forms\Form
    {
        $sidFromQuery = request()->integer('session_id');
        $midFromQuery = request()->integer('manpower_id');

        $parseBp = function (?string $value): array {
            if (! is_string($value)) return [null, null];
            return preg_match('/^\s*(\d{2,3})\s*\/\s*(\d{2,3})\s*$/', $value, $m) ? [(int) $m[1], (int) $m[2]] : [null, null];
        };

        return $form->schema([
            Section::make('Header Sesi')
                ->columns(4)
                ->schema([
                    Select::make('session_id')
                        ->label('Sesi Briefing')
                        ->relationship('session', 'id', fn(EloquentBuilder $query) => $query->orderByDesc('date'))
                        ->getOptionLabelFromRecordUsing(fn($record) => $record->display_label)
                        ->default($sidFromQuery)
                        ->required()
                        ->searchable()
                        ->preload()
                        ->live()
                        ->disabled(fn($record) => filled($record)),

                    Select::make('manpower_id')
                        ->label('Nama MP')
                        ->options(function (Get $get) {
                            $sid = (int) ($get('session_id') ?: request()->integer('session_id'));
                            $query = Manpower::query()->orderBy('name');
                            if ($sid) {
                                $depotId = BriefingSession::whereKey($sid)->value('depot_id');
                                if ($depotId) $query->where('depot_id', $depotId);
                            }
                            return $query->pluck('name','id');
                        })
                        ->default($midFromQuery)
                        ->required()
                        ->searchable()
                        ->preload()
                        ->live()
                        ->rules([
                            function (Get $get, ?BriefingAttendance $record) {
                                $sid = (int) ($get('session_id') ?: request()->integer('session_id'));
                                if (! $sid) return null;
                                return Rule::unique('briefing_attendances','manpower_id')
                                    ->where(fn($query) => $query->where('session_id',$sid))
                                    ->ignore($record?->getKey());
                            },
                        ]),
                ]),

            Grid::make(12)->schema([
                Section::make('Pemeriksaan')
                    ->columnSpan(6)
                    ->columns(2)
                    ->schema([
                        Select::make('attendance_status')
                            ->label('Status')
                            ->options(collect(AttendanceStatus::cases())->mapWithKeys(fn($c)=>[$c->value=>$c->label()]))
                            ->default(AttendanceStatus::Present->value)
                            ->required()
                            ->live(),

                        TextInput::make('temperature')
                            ->label('Suhu (°C)')
                            ->numeric()
                            ->minValue(35)
                            ->maxValue(42)
                            ->visible(fn(Get $get) => $get('attendance_status') === AttendanceStatus::Present->value)
                            ->required(fn(Get $get) => $get('attendance_status') === AttendanceStatus::Present->value),

                        TextInput::make('bp')
                            ->label('Tekanan Darah (mmHg)')
                            ->placeholder('120/80')
                            ->rules(['nullable','regex:/^\s*(\d{2,3})\s*\/\s*(\d{2,3})\s*$/'])
                            ->visible(fn(Get $get) => $get('attendance_status') === AttendanceStatus::Present->value)
                            ->required(fn(Get $get) => $get('attendance_status') === AttendanceStatus::Present->value)
                            ->dehydrated()
                            ->afterStateHydrated(function ($set, $state, ?BriefingAttendance $record) {
                                if (! $record) return;
                                $sys = $record->bp_systolic;
                                $dia = $record->bp_diastolic;
                                $set('bp', ($sys && $dia) ? ($sys.'/'.$dia) : null);
                            })
                            ->afterStateUpdated(function ($set, $state) use ($parseBp) {
                                [$sys, $dia] = $parseBp($state);
                                $set('bp_systolic', $sys);
                                $set('bp_diastolic', $dia);
                            })
                            ->columnSpan(2),

                        Hidden::make('bp_systolic')->dehydrated()->rules(['nullable','integer','min:80','max:200']),
                        Hidden::make('bp_diastolic')->dehydrated()->rules(['nullable','integer','min:40','max:130']),

                        Textarea::make('health_complaint')->label('Keluhan Kesehatan')->rows(2)->maxLength(500)
                            ->visible(fn(Get $get) => $get('attendance_status') === AttendanceStatus::Present->value),

                        Textarea::make('remark')->label('Catatan')->rows(2)->maxLength(500),
                    ]),
            ]),
        ])->columns(1);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->query(fn(): EloquentBuilder => static::getEloquentQuery())
            ->defaultSort('created_at','desc')
            ->columns([
                TextColumn::make('session.date')->label('Hari / Tanggal')->date()->sortable(),
                TextColumn::make('manpower.name')->label('Nama Karyawan')->searchable()->sortable(),

                TextColumn::make('temperature')
                    ->label('Suhu Tubuh')
                    ->state(fn($record) => $record->temperature ? number_format((float)$record->temperature,1).' °C' : '—')
                    ->color(fn($record) => ($record->temperature && ($record->temperature < 36.5 || $record->temperature > 37.6)) ? 'danger' : null)
                    ->sortable(),

                TextColumn::make('bp_display')
                    ->label('Tekanan Darah')
                    ->state(fn($record) => ($record->bp_systolic && $record->bp_diastolic) ? "{$record->bp_systolic}/{$record->bp_diastolic} mmHg" : '—')
                    ->color(function ($record) {
                        if (! $record->bp_systolic || ! $record->bp_diastolic) return null;
                        $ok = ($record->bp_systolic >= 90 && $record->bp_diastolic >= 60) && ($record->bp_systolic <= 120 && $record->bp_diastolic <= 80);
                        return $ok ? null : 'danger';
                    }),

                TextColumn::make('health_complaint')->label('Keluhan Kesehatan')->limit(30)->tooltip(fn($record)=>$record->health_complaint),
                TextColumn::make('remark')->label('Catatan')->limit(30)->tooltip(fn($record)=>$record->remark),

                TextColumn::make('created_at')->label('Dibuat')->since()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('date_range')->label('Rentang Tanggal')
                    ->form([DatePicker::make('from')->label('Dari'), DatePicker::make('to')->label('Sampai')])
                    ->query(function (EloquentBuilder $query, array $data) {
                        if ($data['from'] ?? null) $query->whereHas('session', fn($state)=>$state->whereDate('date','>=',$data['from']));
                        if ($data['to'] ?? null)   $query->whereHas('session', fn($state)=>$state->whereDate('date','<=',$data['to']));
                    }),

                SelectFilter::make('session_id')->label('Sesi')->relationship('session','id')->getOptionLabelFromRecordUsing(fn($record)=>$record->display_label)->searchable()->preload(),
                SelectFilter::make('session.depot')->label('Depot')->relationship('session.depot','name')->searchable()->preload(),
                SelectFilter::make('session.coordinator')->label('PIC')->relationship('session.coordinator','name')->searchable()->preload(),
            ])
            ->headerActions([
                Tables\Actions\Action::make('goCreate')->label('Tambah Absensi')->icon('heroicon-m-plus')->url(function () { $sid = request()->integer('session_id'); return static::getUrl('create', array_filter(['session_id'=>$sid])); }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('Ubah'),
                Tables\Actions\DeleteAction::make()->label('Hapus'),
            ])
            ->bulkActions([Tables\Actions\DeleteBulkAction::make()->label('Hapus Terpilih')]);
    }

    public static function getEloquentQuery(): EloquentBuilder
    {
        return static::getModel()::query()->with([
            'session:id,date,depot_id,coordinator_user_id',
            'session.depot:id,name',
            'session.coordinator:id,name',
            'manpower:id,name,domain',
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListBriefingAttendances::route('/'),
            'create' => Pages\CreateBriefingAttendance::route('/create'),
            'edit'   => Pages\EditBriefingAttendance::route('/{record}/edit'),
        ];
    }
}
