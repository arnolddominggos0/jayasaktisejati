<?php

namespace App\Filament\FC\Resources\BriefingSessionResource\RelationManagers;

use App\Enums\AttendanceStatus;
use App\Enums\PpeCondition;
use App\Models\BriefingAttendance;
use App\Models\BriefingAttendancePpeItem;
use App\Models\Manpower;
use Filament\Forms;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;

class AttendancesRelationManager extends RelationManager
{
    protected static string $relationship = 'attendances';

    protected static ?string $recordTitleAttribute = 'id';

    protected static ?string $title = 'Kehadiran & Pemeriksaan MP';

    public function form(Forms\Form $form): Forms\Form
    {
        $parseBp = function (?string $value): array {
            if (! is_string($value)) {
                return [null, null];
            }

            return preg_match('/^\s*(\d{2,3})\s*\/\s*(\d{2,3})\s*$/', $value, $m)
                ? [(int) $m[1], (int) $m[2]]
                : [null, null];
        };

        return $form->schema([
            Section::make('Data Kehadiran')
                ->columns(2)
                ->schema([
                    Select::make('manpower_id')
                        ->label('Nama MP')
                        ->options(function () {
                            $session = $this->getOwnerRecord();

                            return Manpower::query()
                                ->when($session?->depot_id, fn ($query) => $query->where('depot_id', $session->depot_id))
                                ->orderBy('name')
                                ->pluck('name', 'id');
                        })
                        ->required()
                        ->searchable()
                        ->preload()
                        ->rule(function (?BriefingAttendance $record) {
                            $session = $this->getOwnerRecord();

                            return Rule::unique('briefing_attendances', 'manpower_id')
                                ->where(fn ($query) => $query->where('session_id', $session->id))
                                ->ignore($record?->getKey());
                        }),

                    Select::make('attendance_status')
                        ->label('Status Kehadiran')
                        ->options(collect(AttendanceStatus::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()]))
                        ->default(AttendanceStatus::Present->value)
                        ->required()
                        ->live()
                        ->native(false),
                ]),

            Section::make('Pemeriksaan Kesehatan')
                ->columns(2)
                ->visible(fn (Get $get) => $get('attendance_status') === AttendanceStatus::Present->value)
                ->schema([
                    TextInput::make('temperature')
                        ->label('Suhu Tubuh (°C)')
                        ->numeric()
                        ->minValue(35)
                        ->maxValue(42)
                        ->step(0.1)
                        ->suffix('°C')
                        ->required(fn (Get $get) => $get('attendance_status') === AttendanceStatus::Present->value),

                    TextInput::make('bp')
                        ->label('Tekanan Darah (mmHg)')
                        ->placeholder('120/80')
                        ->rules(['nullable', 'regex:/^\s*(\d{2,3})\s*\/\s*(\d{2,3})\s*$/'])
                        ->required(fn (Get $get) => $get('attendance_status') === AttendanceStatus::Present->value)
                        ->dehydrated()
                        ->afterStateHydrated(function ($set, $state, ?BriefingAttendance $record) {
                            if (! $record) {
                                return;
                            }
                            $set('bp', ($record->bp_systolic && $record->bp_diastolic) ? ($record->bp_systolic.'/'.$record->bp_diastolic) : null);
                        })
                        ->afterStateUpdated(function ($set, $state) use ($parseBp) {
                            [$sys, $dia] = $parseBp($state);
                            $set('bp_systolic', $sys);
                            $set('bp_diastolic', $dia);
                        })
                        ->columnSpan(1),

                    Hidden::make('bp_systolic')->dehydrated(),
                    Hidden::make('bp_diastolic')->dehydrated(),

                    TextInput::make('has_ppe')
                        ->label('APD Lengkap')
                        ->formatStateUsing(fn ($state) => $state ? 'Ya' : 'Tidak')
                        ->disabled()
                        ->dehydrated(false),

                    Textarea::make('health_complaint')
                        ->label('Keluhan Kesehatan')
                        ->rows(2)
                        ->maxLength(500)
                        ->columnSpanFull(),
                ]),

            Section::make('Pemeriksaan APD (Alat Pelindung Diri)')
                ->columns(4)
                ->visible(fn (Get $get) => $get('attendance_status') === AttendanceStatus::Present->value)
                ->schema([
                    Select::make('helm_condition')
                        ->label('Helm')
                        ->options(collect(PpeCondition::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()]))
                        ->default(PpeCondition::Baik->value)
                        ->required(fn (Get $get) => $get('attendance_status') === AttendanceStatus::Present->value)
                        ->native(false)
                        ->dehydrated(false)
                        ->live(),

                    Select::make('rompi_condition')
                        ->label('Rompi')
                        ->options(collect(PpeCondition::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()]))
                        ->default(PpeCondition::Baik->value)
                        ->required(fn (Get $get) => $get('attendance_status') === AttendanceStatus::Present->value)
                        ->native(false)
                        ->dehydrated(false)
                        ->live(),

                    Select::make('sepatu_condition')
                        ->label('Sepatu Safety')
                        ->options(collect(PpeCondition::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()]))
                        ->default(PpeCondition::Baik->value)
                        ->required(fn (Get $get) => $get('attendance_status') === AttendanceStatus::Present->value)
                        ->native(false)
                        ->dehydrated(false)
                        ->live(),

                    Select::make('sarung_tangan_condition')
                        ->label('Sarung Tangan')
                        ->options(collect(PpeCondition::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()]))
                        ->default(PpeCondition::Baik->value)
                        ->required(fn (Get $get) => $get('attendance_status') === AttendanceStatus::Present->value)
                        ->native(false)
                        ->dehydrated(false)
                        ->live(),
                ]),

            Section::make('Catatan')
                ->schema([
                    Textarea::make('remark')
                        ->label('Catatan')
                        ->rows(2)
                        ->maxLength(500),
                ]),
        ]);
    }

    public function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->defaultSort('created_at')
            ->columns([
                TextColumn::make('manpower.name')
                    ->label('Nama MP')
                    ->sortable()
                    ->searchable()
                    ->weight('bold'),

                TextColumn::make('attendance_status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => AttendanceStatus::tryFrom($state)?->label() ?? $state)
                    ->color(fn ($state) => AttendanceStatus::tryFrom($state)?->color() ?? 'gray')
                    ->sortable(),

                TextColumn::make('temperature')
                    ->label('Suhu')
                    ->state(fn ($record) => $record->temperature ? number_format((float) $record->temperature, 1).'°C' : '-')
                    ->color(fn ($record) => ($record->temperature && ($record->temperature < 36.5 || $record->temperature > 37.6)) ? 'danger' : null)
                    ->sortable(),

                TextColumn::make('bp_display')
                    ->label('Tensi')
                    ->state(fn ($record) => ($record->bp_systolic && $record->bp_diastolic) ? "{$record->bp_systolic}/{$record->bp_diastolic}" : '-')
                    ->color(function ($record) {
                        if (! $record->bp_systolic || ! $record->bp_diastolic) {
                            return null;
                        }
                        $ok = ($record->bp_systolic >= 90 && $record->bp_diastolic >= 60) && ($record->bp_systolic <= 140 && $record->bp_diastolic <= 90);

                        return $ok ? null : 'danger';
                    }),

                TextColumn::make('helm_status')
                    ->label('Helm')
                    ->state(fn ($record) => $record->ppeItems()->where('ppe_type', 'helm')->value('condition') ?? '-')
                    ->badge()
                    ->color(fn ($state) => $state === 'baik' ? 'success' : ($state === 'rusak' ? 'danger' : 'warning')),

                TextColumn::make('rompi_status')
                    ->label('Rompi')
                    ->state(fn ($record) => $record->ppeItems()->where('ppe_type', 'rompi')->value('condition') ?? '-')
                    ->badge()
                    ->color(fn ($state) => $state === 'baik' ? 'success' : ($state === 'rusak' ? 'danger' : 'warning')),

                TextColumn::make('sepatu_status')
                    ->label('Sepatu')
                    ->state(fn ($record) => $record->ppeItems()->where('ppe_type', 'sepatu')->value('condition') ?? '-')
                    ->badge()
                    ->color(fn ($state) => $state === 'baik' ? 'success' : ($state === 'rusak' ? 'danger' : 'warning')),

                TextColumn::make('sarung_tangan_status')
                    ->label('Sarung Tangan')
                    ->state(fn ($record) => $record->ppeItems()->where('ppe_type', 'sarung_tangan')->value('condition') ?? '-')
                    ->badge()
                    ->color(fn ($state) => $state === 'baik' ? 'success' : ($state === 'rusak' ? 'danger' : 'warning')),

                TextColumn::make('health_complaint')
                    ->label('Keluhan')
                    ->limit(20)
                    ->tooltip(fn ($record) => $record->health_complaint)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('present')
                    ->label('Hadir')
                    ->query(fn (Builder $query) => $query->where('attendance_status', AttendanceStatus::Present->value)),
                Filter::make('absent')
                    ->label('Tidak Hadir')
                    ->query(fn (Builder $query) => $query->where('attendance_status', AttendanceStatus::Absent->value)),
                Filter::make('sick')
                    ->label('Sakit')
                    ->query(fn (Builder $query) => $query->where('attendance_status', AttendanceStatus::Sick->value)),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Tambah MP')
                    ->mutateFormDataUsing(function (array $data) {
                        $session = $this->getOwnerRecord();
                        $data['session_id'] = $session->id;

                        return $data;
                    })
                    ->after(function (BriefingAttendance $record) {
                        $this->syncPpeItems($record);
                    }),

                Tables\Actions\Action::make('generateAll')
                    ->label('Generate Semua MP Depot')
                    ->icon('heroicon-o-sparkles')
                    ->requiresConfirmation()
                    ->modalDescription('Buat absensi untuk semua MP aktif di depot ini?')
                    ->action(function () {
                        $session = $this->getOwnerRecord();

                        $mpIds = Manpower::query()
                            ->where('depot_id', $session->depot_id)
                            ->where('active', true)
                            ->pluck('id');

                        $created = 0;
                        foreach ($mpIds as $mpId) {
                            $attendance = BriefingAttendance::firstOrCreate(
                                ['session_id' => $session->id, 'manpower_id' => $mpId],
                                ['attendance_status' => AttendanceStatus::Present->value]
                            );

                            if ($attendance->wasRecentlyCreated) {
                                $created++;
                                $this->syncPpeItems($attendance);
                            }
                        }

                        Notification::make()
                            ->title("{$created} MP ditambahkan")
                            ->success()
                            ->send();
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Ubah')
                    ->after(function (BriefingAttendance $record) {
                        $this->syncPpeItems($record);
                    }),
                Tables\Actions\DeleteAction::make()->label('Hapus'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->label('Hapus Terpilih'),
                ]),
            ]);
    }

    private function syncPpeItems(BriefingAttendance $attendance): void
    {
        $data = $this->form->getRawState();

        if ($attendance->attendance_status !== AttendanceStatus::Present->value) {
            return;
        }

        $ppeItems = [
            'helm' => $data['helm_condition'] ?? PpeCondition::Baik->value,
            'rompi' => $data['rompi_condition'] ?? PpeCondition::Baik->value,
            'sepatu' => $data['sepatu_condition'] ?? PpeCondition::Baik->value,
            'sarung_tangan' => $data['sarung_tangan_condition'] ?? PpeCondition::Baik->value,
        ];

        foreach ($ppeItems as $type => $condition) {
            BriefingAttendancePpeItem::updateOrCreate(
                [
                    'attendance_id' => $attendance->id,
                    'ppe_type' => $type,
                ],
                ['condition' => $condition]
            );
        }

        $allGood = collect($ppeItems)->every(fn ($condition) => $condition === PpeCondition::Baik->value);
        $attendance->update(['has_ppe' => $allGood]);

        $session = $attendance->session;
        if ($session) {
            $session->refreshSufficientFlag();
        }
    }
}
