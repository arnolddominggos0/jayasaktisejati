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
use Filament\Forms\Set;
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

        $defaultPpe = fn() => [
            ['ppe_type' => \App\Enums\PpeType::Helm->value,         'condition' => \App\Enums\PpeCondition::Baik->value],
            ['ppe_type' => \App\Enums\PpeType::SarungTangan->value, 'condition' => \App\Enums\PpeCondition::Baik->value],
            ['ppe_type' => \App\Enums\PpeType::Sepatu->value,       'condition' => \App\Enums\PpeCondition::Baik->value],
            ['ppe_type' => \App\Enums\PpeType::Rompi->value,        'condition' => \App\Enums\PpeCondition::Baik->value],
        ];

        $sidFromQuery = request()->integer('session_id');
        $midFromQuery = request()->integer('manpower_id');

        $parseBp = function (?string $v): array {
            if (! is_string($v)) return [null, null];
            return preg_match('/^\s*(\d{2,3})\s*\/\s*(\d{2,3})\s*$/', $v, $m)
                ? [(int) $m[1], (int) $m[2]]
                : [null, null];
        };

        return $form->schema([
            Forms\Components\Section::make('Header Sesi')
                ->columns(4)
                ->schema([
                    Forms\Components\Select::make('session_id')
                        ->label('Sesi Briefing')
                        ->relationship('session', 'id', fn(Builder $query) => $query->orderByDesc('date'))
                        ->getOptionLabelFromRecordUsing(fn($record) => $record->display_label)
                        ->default($sidFromQuery)
                        ->required()
                        ->searchable()
                        ->preload()
                        ->live()
                        ->disabled(fn($record) => filled($record)),

                    Forms\Components\Select::make('manpower_id')
                        ->label('Manpower')
                        ->options(function ($get) {
                            $sid = (int) ($get('session_id') ?: request()->integer('session_id'));
                            $query = Manpower::query()->orderBy('name');
                            if ($sid) {
                                $depotId = BriefingSession::whereKey($sid)->value('depot_id');
                                if ($depotId) $query->where('depot_id', $depotId);
                            }
                            return $query->pluck('name', 'id');
                        })
                        ->default($midFromQuery)
                        ->required()
                        ->searchable()
                        ->preload()
                        ->live()
                        ->rules([
                            function ($get, ?BriefingAttendance $record) {
                                $sid = (int) ($get('session_id') ?: request()->integer('session_id'));
                                if (! $sid) return null;
                                return Rule::unique('briefing_attendances', 'manpower_id')
                                    ->where(fn($query) => $query->where('session_id', $sid))
                                    ->ignore($record?->getKey());
                            },
                        ]),
                ]),

            Forms\Components\Grid::make(12)->schema([
                Forms\Components\Section::make('Pemeriksaan')
                    ->columnSpan(5)
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('attendance_status')
                            ->label('Status')
                            ->options(collect(AttendanceStatus::cases())->mapWithKeys(fn($c) => [$c->value => $c->label()]))
                            ->required()
                            ->live(),

                        Forms\Components\TextInput::make('temperature')
                            ->label('Suhu (°C)')
                            ->numeric()
                            ->minValue(35)
                            ->maxValue(42)
                            ->visible(fn($get) => $get('attendance_status') === AttendanceStatus::Present->value)
                            ->required(fn($get) => $get('attendance_status') === AttendanceStatus::Present->value),

                        Forms\Components\TextInput::make('bp')
                            ->label('Tekanan Darah (mmHg)')
                            ->placeholder('120/80')
                            ->rules([
                                'nullable',
                                'regex:/^\s*(\d{2,3})\s*\/\s*(\d{2,3})\s*$/',
                            ])
                            ->visible(fn($get) => $get('attendance_status') === AttendanceStatus::Present->value)
                            ->required(fn($get) => $get('attendance_status') === AttendanceStatus::Present->value)
                            ->dehydrated()
                            ->afterStateHydrated(function ($set, $state, ?BriefingAttendance $record) {
                                if (! $record) return;
                                $sys = $record->bp_systolic;
                                $dia = $record->bp_diastolic;
                                $set('bp', ($sys && $dia) ? ($sys . '/' . $dia) : null);
                            })
                            ->afterStateUpdated(function ($set, $state) use ($parseBp) {
                                [$sys, $dia] = $parseBp($state);
                                $set('bp_systolic', $sys);
                                $set('bp_diastolic', $dia);
                            })
                            ->columnSpan(2),

                        Forms\Components\Hidden::make('bp_systolic')
                            ->dehydrated()
                            ->rules(['nullable', 'integer', 'min:80', 'max:200']),
                        Forms\Components\Hidden::make('bp_diastolic')
                            ->dehydrated()
                            ->rules(['nullable', 'integer', 'min:40', 'max:130']),
                        Forms\Components\Textarea::make('health_complaint')
                            ->label('Keluhan Kesehatan')
                            ->rows(2)
                            ->maxLength(500)
                            ->visible(fn($get) => $get('attendance_status') === AttendanceStatus::Present->value),
                    ]),

                Forms\Components\Section::make('APD')
                    ->columnSpan(7)
                    ->schema([
                        Forms\Components\Repeater::make('ppeItems')
                            ->label('Detail APD')
                            ->relationship('ppeItems')
                            ->grid(2)
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false)
                            ->visible(fn($get) => $get('attendance_status') === \App\Enums\AttendanceStatus::Present->value)
                            ->schema([
                                Forms\Components\Select::make('ppe_type')
                                    ->label('Jenis APD')
                                    ->options(collect(\App\Enums\PpeType::cases())->mapWithKeys(fn($c) => [$c->value => $c->label()]))
                                    ->disabled()
                                    ->dehydrated(),
                                Forms\Components\Select::make('condition')
                                    ->label('Kondisi')
                                    ->options(collect(\App\Enums\PpeCondition::cases())->mapWithKeys(fn($c) => [$c->value => $c->label()]))
                                    ->default(\App\Enums\PpeCondition::Baik->value)
                                    ->required(),
                                Forms\Components\TextInput::make('remark')
                                    ->label('Catatan')
                                    ->maxLength(100),
                            ])
                            ->default(function ($record) use ($defaultPpe) {
                                if ($record && $record->ppeItems()->exists()) return null;
                                return $defaultPpe();
                            })
                            ->afterStateHydrated(function ($state, $set, ?\App\Models\BriefingAttendance $record) use ($defaultPpe) {
                                if ($record && (empty($state) || count($state) === 0)) {
                                    $set('ppeItems', $defaultPpe());
                                }
                            })
                            ->afterStateUpdated(function ($set, $get) {
                                $items = collect($get('ppeItems') ?? []);
                                $complete = $items->count() === 4
                                    && $items->every(fn($it) => ($it['condition'] ?? null) === \App\Enums\PpeCondition::Baik->value);
                                $set('has_ppe', $complete);
                            }),
                    ]),
            ]),
        ])->columns(1);
    }


    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $query->withCount([
                    'ppeItems as ppe_total',
                    'ppeItems as ppe_bad' => fn($query) => $query->where('condition', '!=', PpeCondition::Baik->value),
                ]);
                if ($sid = request()->integer('session_id'))  $query->where('session_id', $sid);
                if ($mid = request()->integer('manpower_id')) $query->where('manpower_id', $mid);
            })
            ->columns([
                Tables\Columns\TextColumn::make('session.date')->label('Tanggal')->date(),
                Tables\Columns\TextColumn::make('session.depot.name')->label('Depot'),
                Tables\Columns\TextColumn::make('session.coordinator.name')->label('Koordinator'),
                Tables\Columns\TextColumn::make('manpower.name')->label('Nama MP')->searchable(),
                Tables\Columns\TextColumn::make('attendance_status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn($state) => $state instanceof AttendanceStatus ? $state->label() : (string) $state)
                    ->color(fn($state) => $state instanceof AttendanceStatus ? $state->color() : 'gray'),
                Tables\Columns\TextColumn::make('ppe_total')
                    ->label('APD')
                    ->state(fn($record) => $record->ppe_total
                        ? ($record->ppe_bad ? "Tidak lengkap: {$record->ppe_bad}/{$record->ppe_total}" : "Lengkap ({$record->ppe_total})")
                        : '—')
                    ->badge()
                    ->color(fn($record) => $record->ppe_bad ? 'danger' : ($record->ppe_total ? 'success' : 'gray')),
                Tables\Columns\TextColumn::make('temperature')->label('Suhu'),
                Tables\Columns\TextColumn::make('bp_systolic')
                    ->label('Tekanan Darah')
                    ->state(fn($record) => ($record->bp_systolic && $record->bp_diastolic) ? "{$record->bp_systolic}/{$record->bp_diastolic}" : '—'),

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
