<?php

namespace App\Filament\Resources\BriefingSessionResource\RelationManagers;

use App\Enums\AttendanceStatus;
use App\Models\BriefingAttendance;
use App\Models\BriefingSession;
use App\Models\Manpower;
use Filament\Forms;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Validation\Rule;

class AttendanceRelationManager extends RelationManager
{
    protected static string $relationship = 'attendances';
    protected static ?string $recordTitleAttribute = 'id';
    protected static ?string $title = 'Absensi & Checksheet';

    public function form(Forms\Form $form): Forms\Form
    {
        $parseBp = function (?string $value): array {
            if (! is_string($value)) return [null, null];
            return preg_match('/^\s*(\d{2,3})\s*\/\s*(\d{2,3})\s*$/', $value, $m)
                ? [(int) $m[1], (int) $m[2]]
                : [null, null];
        };

        return $form->schema([
            Section::make('Data Absensi')
                ->columns(2)
                ->schema([
                    Select::make('manpower_id')
                        ->label('Nama MP')
                        ->options(function () {
                            /** @var \App\Models\BriefingSession $session */
                            $session = $this->getOwnerRecord();

                            return \App\Models\Manpower::query()
                                ->when($session?->depot_id, fn($query) => $query->where('depot_id', $session->depot_id))
                                ->orderBy('name')
                                ->pluck('name', 'id');
                        })
                        ->required()
                        ->searchable()
                        ->preload()
                        ->rule(function (?\App\Models\BriefingAttendance $record) {
                            /** @var \App\Models\BriefingSession $session */
                            $session = $this->getOwnerRecord();

                            return \Illuminate\Validation\Rule::unique('briefing_attendances', 'manpower_id')
                                ->where(fn($query) => $query->where('session_id', $session->id))
                                ->ignore($record?->getKey());
                        }),

                    Select::make('attendance_status')
                        ->label('Status')
                        ->options(collect(AttendanceStatus::cases())->mapWithKeys(fn($c) => [$c->value => $c->label()]))
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
                        ->rules(['nullable', 'regex:/^\s*(\d{2,3})\s*\/\s*(\d{2,3})\s*$/'])
                        ->visible(fn(Get $get) => $get('attendance_status') === AttendanceStatus::Present->value)
                        ->required(fn(Get $get) => $get('attendance_status') === AttendanceStatus::Present->value)
                        ->dehydrated()
                        ->afterStateHydrated(function ($set, $state, ?BriefingAttendance $record) {
                            if (! $record) return;
                            $set('bp', ($record->bp_systolic && $record->bp_diastolic) ? ($record->bp_systolic . '/' . $record->bp_diastolic) : null);
                        })
                        ->afterStateUpdated(function ($set, $state) use ($parseBp) {
                            [$sys, $dia] = $parseBp($state);
                            $set('bp_systolic', $sys);
                            $set('bp_diastolic', $dia);
                        })
                        ->columnSpan(2),

                    Hidden::make('bp_systolic')->dehydrated()->rules(['nullable', 'integer', 'min:80', 'max:200']),
                    Hidden::make('bp_diastolic')->dehydrated()->rules(['nullable', 'integer', 'min:40', 'max:130']),

                    Textarea::make('health_complaint')
                        ->label('Keluhan Kesehatan')
                        ->rows(2)
                        ->maxLength(500)
                        ->visible(fn(Get $get) => $get('attendance_status') === AttendanceStatus::Present->value),

                    Textarea::make('remark')->label('Catatan')->rows(2)->maxLength(500),
                ]),
        ]);
    }

    public function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('manpower.name')->label('Nama Karyawan')->sortable()->searchable(),
                TextColumn::make('temperature')
                    ->label('Suhu')
                    ->state(fn($record) => $record->temperature ? number_format((float) $record->temperature, 1) . ' °C' : '—')
                    ->color(fn($record) => ($record->temperature && ($record->temperature < 36.5 || $record->temperature > 37.6)) ? 'danger' : null),
                TextColumn::make('bp')
                    ->label('Tekanan Darah')
                    ->state(fn($record) => ($record->bp_systolic && $record->bp_diastolic) ? "{$record->bp_systolic}/{$record->bp_diastolic} mmHg" : '—')
                    ->color(function ($record) {
                        if (! $record->bp_systolic || ! $record->bp_diastolic) return null;
                        $ok = ($record->bp_systolic >= 90 && $record->bp_diastolic >= 60) && ($record->bp_systolic <= 120 && $record->bp_diastolic <= 80);
                        return $ok ? null : 'danger';
                    }),
                TextColumn::make('health_complaint')->label('Keluhan')->limit(24)->tooltip(fn($record) => $record->health_complaint),
                TextColumn::make('remark')->label('Catatan')->limit(24)->tooltip(fn($record) => $record->remark),
                TextColumn::make('created_at')->label('Dibuat')->since()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('hadir')->label('Hadir')->query(fn(EloquentBuilder $query) => $query->where('attendance_status', AttendanceStatus::Present->value)),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()->label('Tambah MP'),
                // 2) header action generateAll
                Tables\Actions\Action::make('generateAll')
                    ->label('Generate dari MP Depot')
                    ->icon('heroicon-m-sparkles')
                    ->requiresConfirmation()
                    ->action(function () {
                        /** @var \App\Models\BriefingSession $session */
                        $session = $this->getOwnerRecord();

                        $mpIds = \App\Models\Manpower::query()
                            ->where('depot_id', $session->depot_id)
                            ->pluck('id')
                            ->all();

                        foreach ($mpIds as $mpId) {
                            \App\Models\BriefingAttendance::firstOrCreate(
                                ['session_id' => $session->id, 'manpower_id' => $mpId],
                                ['attendance_status' => \App\Enums\AttendanceStatus::Present->value]
                            );
                        }
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
}
