<?php

namespace App\Filament\Resources;

use App\Enums\AttendanceStatus;
use App\Enums\MPDomain;
use App\Enums\PpeCondition;
use App\Enums\PpeType;
use App\Filament\Resources\BriefingAttendanceResource\Pages;
use App\Models\BriefingAttendance;
use App\Models\BriefingSession;
use App\Models\Manpower;
use Filament\Forms;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
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

    public static function form(Forms\Form $form): Forms\Form
    {
        $sidFromQuery = request()->has('session_id') ? (int) request()->query('session_id') : null;
        $midFromQuery = request()->has('manpower_id') ? (int) request()->query('manpower_id') : null;

        $routeParam = request()->route('record');
        $currentAttendanceId = is_object($routeParam) ? $routeParam->getKey() : ($routeParam ?: null);

        return $form->schema([
            Forms\Components\Select::make('session_id')
                ->label('Sesi Briefing')
                ->relationship('session', 'id', modifyQueryUsing: fn(Builder $query) => $query->orderByDesc('date'))
                ->getOptionLabelFromRecordUsing(fn($record) => $record->display_label)
                ->default($sidFromQuery)
                ->required()
                ->searchable()
                ->preload()
                ->reactive(),

            Forms\Components\Select::make('manpower_id')
                ->label('Manpower')
                ->options(function (Get $get) use ($midFromQuery, $currentAttendanceId) {
                    $sessionId = (int) ($get('session_id') ?: request()->query('session_id'));

                    $query = Manpower::query()->orderBy('name');

                    $depotId = null;
                    if ($sessionId) {
                        $depotId = BriefingSession::query()->whereKey($sessionId)->value('depot_id');
                        if ($depotId) {
                            $query->where('depot_id', $depotId);
                        }
                    }

                    $forceIds = [];

                    if ($currentAttendanceId) {
                        $currentMpId = BriefingAttendance::query()->whereKey($currentAttendanceId)->value('manpower_id');
                        if ($currentMpId) $forceIds[] = (int) $currentMpId;
                    }

                    if ($midFromQuery) {
                        $forceIds[] = (int) $midFromQuery;
                    }

                    if (!empty($forceIds)) {
                        $query->orWhereIn('id', array_unique($forceIds));
                    }

                    return $query->pluck('name', 'id')->toArray();
                })
                ->default($midFromQuery)
                ->required()
                ->searchable()
                ->preload()
                ->reactive(),

            Forms\Components\Select::make('attendance_status')
                ->label('Status')
                ->options(collect(AttendanceStatus::cases())->mapWithKeys(fn($c) => [$c->value => $c->label()]))
                ->required(),

            Forms\Components\TextInput::make('temperature')
                ->label('Suhu (°C)')
                ->numeric()
                ->minValue(35)
                ->maxValue(42)
                ->rules(['nullable', 'numeric']),

            Forms\Components\TextInput::make('bp')
                ->label('Tekanan Darah (mmHg)')
                ->placeholder('120/80')
                ->rules(['nullable', 'regex:/^\d{2,3}\/\d{2,3}$/']),

            Forms\Components\Toggle::make('has_ppe')->label('APD Lengkap')->default(true),
            Forms\Components\Select::make('attendance_status')
                ->label('Status')
                ->options(collect(\App\Enums\AttendanceStatus::cases())->mapWithKeys(fn($c) => [$c->value => $c->label()]))
                ->required()
                ->reactive(),

            Forms\Components\TextInput::make('temperature')
                ->label('Suhu (°C)')
                ->numeric()->minValue(35)->maxValue(42)
                ->rules(['nullable', 'numeric'])
                ->visible(fn(Get $get) => $get('attendance_status') === \App\Enums\AttendanceStatus::Present->value)
                ->required(fn(Get $get) => $get('attendance_status') === \App\Enums\AttendanceStatus::Present->value),

            Forms\Components\TextInput::make('bp')
                ->label('Tekanan Darah (mmHg)')
                ->placeholder('120/80')
                ->rules(['nullable', 'regex:/^\d{2,3}\/\d{2,3}$/'])
                ->visible(fn(Get $get) => $get('attendance_status') === \App\Enums\AttendanceStatus::Present->value),

            Forms\Components\Toggle::make('has_ppe')
                ->label('APD Lengkap')
                ->default(true)
                ->visible(fn(Get $get) => $get('attendance_status') === \App\Enums\AttendanceStatus::Present->value),

            Forms\Components\Repeater::make('ppeItems')
                ->label('Detail APD')
                ->relationship('ppeItems')         
                ->visible(fn(Get $get) => $get('attendance_status') === \App\Enums\AttendanceStatus::Present->value)
                ->columns(3)
                ->schema([
                    Forms\Components\Select::make('ppe_type')
                        ->label('Jenis APD')
                        ->options(collect(PpeType::cases())->mapWithKeys(fn($c) => [$c->value => $c->label()]))
                        ->required(),

                    Forms\Components\Select::make('condition')
                        ->label('Kondisi')
                        ->options(collect(PpeCondition::cases())->mapWithKeys(fn($c) => [$c->value => $c->label()]))
                        ->default(PpeCondition::Baik->value)
                        ->required(),

                    Forms\Components\TextInput::make('remark')
                        ->label('Catatan')
                        ->maxLength(100),
                ])
                ->createItemButtonLabel('Tambah Item APD')
                ->defaultItems(0)
                ->helperText('Isi item sesuai list: Helm, Sarung Tangan, Sepatu, Rompi. Duplikasi otomatis ditolak.'),
        ])->columns(2);
    }


    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                if ($sid = request()->integer('session_id'))  $query->where('session_id', $sid);
                if ($mid = request()->integer('manpower_id')) $query->where('manpower_id', $mid);
            })
            ->columns([
                Tables\Columns\TextColumn::make('session.date')->label('Tanggal')->date(),
                Tables\Columns\TextColumn::make('session.depot.name')->label('Depot'),
                Tables\Columns\TextColumn::make('session.coordinator.name')->label('Koordinator'),
                Tables\Columns\TextColumn::make('manpower.name')->label('Nama MP')->searchable(),
                Tables\Columns\TextColumn::make('attendance_status')->label('Status')->badge()
                    ->formatStateUsing(fn($state) => $state instanceof AttendanceStatus ? $state->label() : (string) $state)
                    ->color(fn($state) => $state instanceof AttendanceStatus ? $state->color() : 'gray'),
                Tables\Columns\IconColumn::make('has_ppe')->label('APD')->boolean(),
                Tables\Columns\TextColumn::make('temperature')->label('Suhu'),
                Tables\Columns\TextColumn::make('bp')->label('TD'),
                Tables\Columns\TextColumn::make('remark')->label('Catatan')->limit(30),
                Tables\Columns\TextColumn::make('created_at')->since()->label('Dibuat'),
            ])
            ->filters([
                Tables\Filters\Filter::make('date_range')
                    ->label('Rentang Tanggal')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('Dari'),
                        Forms\Components\DatePicker::make('to')->label('Sampai'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (!empty($data['from'])) $query->whereHas('session', fn($state) => $state->whereDate('date', '>=', $data['from']));
                        if (!empty($data['to']))   $query->whereHas('session', fn($state) => $state->whereDate('date', '<=', $data['to']));
                    }),

                Tables\Filters\SelectFilter::make('session')
                    ->label('Sesi')
                    ->relationship('session', 'id')
                    ->getOptionLabelFromRecordUsing(fn($record) => $record->display_label)
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('session.coordinator')
                    ->label('Koordinator')
                    ->relationship('session.coordinator', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('session.depot')
                    ->label('Depot')
                    ->relationship('session.depot', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('manpower.domain')
                    ->label('Domain MP')
                    ->options(collect(MPDomain::cases())->mapWithKeys(fn($c) => [$c->value => $c->label()])),
            ])

            ->emptyStateIcon('heroicon-o-clipboard-document-list')
            ->emptyStateHeading('Belum ada absensi')
            ->emptyStateDescription('Tambahkan absensi untuk sesi ini menggunakan tombol di atas.')

            ->headerActions([
                Tables\Actions\Action::make('goCreate')
                    ->label('Tambah Absensi')
                    ->icon('heroicon-m-plus')
                    ->url(function () {
                        $sid = request()->integer('session_id');
                        return static::getUrl('create', array_filter(['session_id' => $sid]));
                    }),

                Tables\Actions\Action::make('quickAdd')
                    ->label('Tambah Cepat (Multi MP)')
                    ->icon('heroicon-m-bolt')
                    ->modalHeading('Tambah Cepat Absensi')
                    ->form(function () {
                        $sid = request()->integer('session_id');
                        return [
                            Forms\Components\Select::make('session_id')
                                ->label('Sesi Briefing')
                                ->options(
                                    BriefingSession::query()->orderByDesc('date')->get()->pluck('display_label', 'id')->toArray()
                                )
                                ->default($sid ?: null)
                                ->required()
                                ->reactive(),
                            Forms\Components\Select::make('manpower_ids')
                                ->label('Pilih MP')
                                ->multiple()
                                ->options(
                                    Manpower::query()->orderBy('name')->pluck('name', 'id')->toArray()
                                )
                                ->required()
                                ->searchable(),
                            Forms\Components\Select::make('attendance_status')
                                ->label('Status')
                                ->options(
                                    collect(AttendanceStatus::cases())->mapWithKeys(fn($c) => [$c->value => $c->label()])->toArray()
                                )
                                ->default(AttendanceStatus::Present->value)
                                ->required(),
                            Forms\Components\Toggle::make('has_ppe')->label('APD Lengkap')->default(true),
                        ];
                    })
                    ->action(function (array $data) {
                        foreach ((array) ($data['manpower_ids'] ?? []) as $mid) {
                            BriefingAttendance::firstOrCreate(
                                ['session_id' => (int) $data['session_id'], 'manpower_id' => (int) $mid],
                                ['attendance_status' => $data['attendance_status'], 'has_ppe' => (bool) $data['has_ppe']],
                            );
                        }
                    })
                    ->successNotificationTitle('Absensi cepat disimpan'),
            ])

            ->actions([
                Tables\Actions\EditAction::make()->label('Ubah'),
                Tables\Actions\DeleteAction::make()->label('Hapus'),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()->label('Hapus Terpilih'),
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
