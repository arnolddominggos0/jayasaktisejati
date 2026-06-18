<?php

namespace App\Filament\FC\Resources\BriefingSessionResource\RelationManagers;

use App\Enums\AttendanceStatus;
use App\Models\AttendanceHealthLog;
use App\Models\BriefingAttendance;
use App\Models\Manpower;
use Filament\Forms;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\Rule;

class AttendancesRelationManager extends RelationManager
{
    protected static string $relationship = 'attendances';

    protected static ?string $recordTitleAttribute = 'id';

    protected static ?string $title = 'Kehadiran & Pemeriksaan MP';

    // ═══════════════════════════════════════════════════════════════════════
    // FORM
    // ═══════════════════════════════════════════════════════════════════════

    public function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            $this->dataKehadiranSection(),
            $this->makePemeriksaanSection(),
            $this->statusAkhirSection(),
            $this->catatanSection(),
        ]);
    }

    // ─── Form section builders ───────────────────────────────────────────────

    /**
     * DATA KEHADIRAN — nama MP (reguler) + status kehadiran.
     */
    private function dataKehadiranSection(): Section
    {
        return Section::make('Data Kehadiran')
            ->columns(2)
            ->schema([

                Select::make('manpower_id')
                    ->label('Nama MP')
                    ->options(function () {
                        $session = $this->getOwnerRecord();

                        return Manpower::query()
                            ->when(
                                $session?->depot_id,
                                fn ($q) => $q->where('depot_id', $session->depot_id)
                            )
                            ->orderBy('name')
                            ->pluck('name', 'id');
                    })
                    ->required()
                    ->searchable()
                    ->preload()
                    ->rule(function (?BriefingAttendance $record) {
                        $session = $this->getOwnerRecord();

                        return Rule::unique('briefing_attendances', 'manpower_id')
                            ->where(fn ($q) => $q->where('session_id', $session->id))
                            ->ignore($record?->getKey());
                    }),

                Select::make('attendance_status')
                    ->label('Status Kehadiran')
                    ->options(
                        collect(AttendanceStatus::cases())
                            ->mapWithKeys(fn ($c) => [$c->value => $c->label()])
                    )
                    ->default(AttendanceStatus::Present->value)
                    ->required()
                    ->live()
                    ->native(false),

            ]);
    }

    /**
     * DATA BACKUP — nama bebas (bukan dari tabel manpower) + status kehadiran.
     * Digunakan oleh "Tambah Backup MP" action.
     */
    private function dataBackupSection(): Section
    {
        return Section::make('Data Backup MP')
            ->columns(2)
            ->schema([

                TextInput::make('backup_name')
                    ->label('Nama Backup MP')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('Nama lengkap backup MP'),

                Select::make('attendance_status')
                    ->label('Status Kehadiran')
                    ->options(
                        collect(AttendanceStatus::cases())
                            ->mapWithKeys(fn ($c) => [$c->value => $c->label()])
                    )
                    ->default(AttendanceStatus::Present->value)
                    ->required()
                    ->live()
                    ->native(false),

            ]);
    }

    /**
     * PEMERIKSAAN MP — digunakan oleh reguler & backup.
     *
     * Section muncul hanya jika attendance_status = present.
     * Keluhan hanya muncul + wajib jika ada vital yang abnormal.
     *
     * signature_path adalah legacy field (nullable) — tidak dikumpulkan via Filament.
     */
    private function makePemeriksaanSection(): Section
    {
        /*
         * Abnormal vitals threshold:
         *   Suhu     : 35.5 – 37.5 °C
         *   Sistolik : 90   – 120  mmHg
         *   Diastolik: 60   – 80   mmHg
         */
        $isVitalsAbnormal = static function (Get $get): bool {
            if ($get('attendance_status') !== AttendanceStatus::Present->value) {
                return false;
            }

            $temp = (float) ($get('temperature')  ?? 0);
            $sys  = (int)   ($get('bp_systolic')   ?? 0);
            $dia  = (int)   ($get('bp_diastolic')  ?? 0);

            if ($temp > 0 && ($temp < 35.5 || $temp > 37.5)) {
                return true;
            }
            if ($sys > 0 && ($sys < 90 || $sys > 120)) {
                return true;
            }
            if ($dia > 0 && ($dia < 60 || $dia > 80)) {
                return true;
            }

            return false;
        };

        $isPresent = fn (Get $get) => $get('attendance_status') === AttendanceStatus::Present->value;

        return Section::make('Pemeriksaan MP')
            ->columns(2)
            ->visible($isPresent)
            ->schema([

                TextInput::make('temperature')
                    ->label('Suhu Tubuh')
                    ->numeric()
                    ->minValue(35)
                    ->maxValue(42)
                    ->step(0.1)
                    ->suffix('°C')
                    ->helperText('Normal: 35.5 °C – 37.5 °C')
                    ->required($isPresent)
                    ->live(onBlur: true)
                    ->columnSpanFull(),

                TextInput::make('bp_systolic')
                    ->label('TD Sistolik')
                    ->numeric()
                    ->minValue(60)
                    ->maxValue(250)
                    ->suffix('mmHg')
                    ->helperText('Normal: 90 – 120 mmHg')
                    ->required($isPresent)
                    ->live(onBlur: true),

                TextInput::make('bp_diastolic')
                    ->label('TD Diastolik')
                    ->numeric()
                    ->minValue(40)
                    ->maxValue(150)
                    ->suffix('mmHg')
                    ->helperText('Normal: 60 – 80 mmHg')
                    ->required($isPresent)
                    ->live(onBlur: true),

                // APD — compact toggle button pair (Ya / Tidak)
                ToggleButtons::make('has_ppe')
                    ->label('APD Lengkap')
                    ->boolean()
                    ->inline()
                    ->grouped()
                    ->default(true)
                    ->live()
                    ->columnSpanFull(),

                // ⚠ Warning — muncul hanya jika vital di luar batas normal
                Placeholder::make('vitals_abnormal_notice')
                    ->label('')
                    ->content(fn () => new HtmlString(
                        '<div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3'
                        . ' text-sm font-medium text-amber-800 dark:border-amber-700/50'
                        . ' dark:bg-amber-900/20 dark:text-amber-300">'
                        . '⚠&nbsp; Pemeriksaan tidak normal. Mohon isi keluhan kesehatan.'
                        . '</div>'
                    ))
                    ->visible($isVitalsAbnormal)
                    ->columnSpanFull(),

                // Keluhan — muncul & wajib hanya jika vital abnormal
                Textarea::make('health_complaint')
                    ->label('Keluhan Kesehatan')
                    ->rows(3)
                    ->maxLength(500)
                    ->visible($isVitalsAbnormal)
                    ->required($isVitalsAbnormal)
                    ->columnSpanFull(),

            ]);
    }

    /**
     * STATUS AKHIR — pratinjau langsung di form (create & edit).
     *
     * - Tidak hadir  → badge abu
     * - has_ppe=false → badge amber (APD Tidak Lengkap) — live dari form state
     * - Edit mode     → status aktual dari $record->final_mp_status
     * - Create mode   → "Belum Dinilai" (evaluasi oleh sistem health check)
     */
    private function statusAkhirSection(): Section
    {
        $colorMap = [
            'Siap Kerja'              => 'emerald',
            'APD Tidak Lengkap'       => 'amber',
            'Istirahat 30 Menit'      => 'amber',
            'Perlu Pemeriksaan Ulang' => 'amber',
            'Berobat'                 => 'orange',
            'Dipulangkan'             => 'rose',
            'Tidak Fit'               => 'rose',
            'Tidak Hadir'             => 'gray',
        ];

        return Section::make('Status Akhir')
            ->description('Pratinjau evaluasi sistem berdasarkan data pemeriksaan saat ini.')
            ->schema([

                Placeholder::make('final_status_display')
                    ->label('Status')
                    ->live()
                    ->content(function (Get $get, $record) use ($colorMap): HtmlString {
                        $attendStatus = $get('attendance_status');

                        // Tidak hadir → langsung gray
                        if ($attendStatus !== AttendanceStatus::Present->value) {
                            return new HtmlString(
                                '<span class="inline-flex items-center rounded-full px-3 py-1 text-sm font-semibold'
                                . ' bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300">Tidak Hadir</span>'
                            );
                        }

                        // APD tidak lengkap — deteksi langsung dari form state (sebelum save)
                        $hasPpe = $get('has_ppe');
                        if ($hasPpe === false || $hasPpe === 0 || $hasPpe === '0') {
                            return new HtmlString(
                                '<span class="inline-flex items-center rounded-full px-3 py-1 text-sm font-semibold'
                                . ' bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300">APD Tidak Lengkap</span>'
                            );
                        }

                        // Edit mode — tampilkan status tersimpan dari record
                        if ($record !== null) {
                            $status = $record->final_mp_status ?? '—';
                            $color  = $colorMap[$status] ?? 'gray';

                            return new HtmlString(
                                "<span class=\"inline-flex items-center rounded-full px-3 py-1 text-sm font-semibold"
                                . " bg-{$color}-100 text-{$color}-800"
                                . " dark:bg-{$color}-900/30 dark:text-{$color}-300\">"
                                . e($status)
                                . '</span>'
                            );
                        }

                        // Create mode — belum bisa dinilai tanpa hasil health check
                        return new HtmlString(
                            '<span class="inline-flex items-center rounded-full px-3 py-1 text-sm font-medium'
                            . ' bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400">⏳&nbsp; Belum Dinilai</span>'
                            . '<span class="ml-2 text-xs text-gray-400 dark:text-gray-500"> — dievaluasi otomatis saat simpan</span>'
                        );
                    }),

                Select::make('fit_status')
                    ->label('Override Evaluasi')
                    ->placeholder('— Otomatis (dari evaluator) —')
                    ->options([
                        'FIT'       => 'FIT',
                        'TIDAK FIT' => 'Tidak FIT',
                    ])
                    ->nullable()
                    ->visible(fn ($record) => $record !== null)
                    ->helperText('Pilih untuk mengganti hasil evaluator otomatis. Kosongkan kembali untuk membiarkan sistem mengevaluasi ulang.'),

            ]);
    }

    /**
     * CATATAN — opsional, selalu visible, collapsed by default.
     */
    private function catatanSection(): Section
    {
        return Section::make('Catatan')
            ->collapsed()
            ->schema([

                Textarea::make('remark')
                    ->label('Catatan')
                    ->rows(2)
                    ->maxLength(500)
                    ->placeholder('Keterangan tambahan (opsional)'),

            ]);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // TABLE
    // ═══════════════════════════════════════════════════════════════════════

    public function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->defaultSort('created_at')
            ->columns([

                // ── Nama MP ────────────────────────────────────────────────
                TextColumn::make('manpower.name')
                    ->label('Nama MP')
                    ->sortable()
                    ->searchable()
                    ->weight('bold')
                    ->formatStateUsing(function ($state, $record) {
                        if ($record->is_backup) {
                            return ($record->backup_name ?? $state) . ' (Backup)';
                        }

                        return $state ?? '-';
                    }),

                // ── Status Akhir (badge, prominent) ───────────────────────
                TextColumn::make('final_mp_status')
                    ->label('Status Akhir')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state ?? 'Belum Dinilai')
                    ->color(fn ($state) => match ($state) {
                        'Siap Kerja'              => 'success',
                        'Tidak Fit'               => 'danger',
                        'Dipulangkan'             => 'danger',
                        'APD Tidak Lengkap'       => 'warning',
                        'Berobat'                 => 'warning',
                        'Istirahat 30 Menit'      => 'info',
                        'Perlu Pemeriksaan Ulang' => 'warning',
                        'Tidak Hadir'             => 'gray',
                        default                   => 'gray',
                    })
                    ->sortable(false),

                // ── Status Kehadiran ───────────────────────────────────────
                TextColumn::make('attendance_status')
                    ->label('Kehadiran')
                    ->badge()
                    ->formatStateUsing(function ($state) {
                        $enum = $state instanceof AttendanceStatus
                            ? $state
                            : AttendanceStatus::tryFrom((string) $state);

                        return $enum?->label() ?? (string) $state;
                    })
                    ->color(function ($state) {
                        $enum = $state instanceof AttendanceStatus
                            ? $state
                            : AttendanceStatus::tryFrom((string) $state);

                        return $enum?->color() ?? 'gray';
                    })
                    ->sortable(),

                // ── Suhu ───────────────────────────────────────────────────
                TextColumn::make('temperature')
                    ->label('Suhu')
                    ->state(fn ($record) => $record->temperature
                        ? number_format((float) $record->temperature, 1) . '°C'
                        : '-')
                    ->color(fn ($record) => ($record->temperature
                        && ($record->temperature < 35.5 || $record->temperature > 37.5))
                        ? 'danger'
                        : null)
                    ->sortable(),

                // ── Tensi ──────────────────────────────────────────────────
                TextColumn::make('bp_display')
                    ->label('Tensi')
                    ->state(fn ($record) => ($record->bp_systolic && $record->bp_diastolic)
                        ? "{$record->bp_systolic}/{$record->bp_diastolic}"
                        : '-')
                    ->color(function ($record) {
                        if (! $record->bp_systolic || ! $record->bp_diastolic) {
                            return null;
                        }
                        $ok = ($record->bp_systolic  >= 90  && $record->bp_diastolic >= 60)
                            && ($record->bp_systolic  <= 120 && $record->bp_diastolic <= 80);

                        return $ok ? null : 'danger';
                    }),

                // ── APD — single badge ─────────────────────────────────────
                TextColumn::make('has_ppe')
                    ->label('APD')
                    ->badge()
                    ->formatStateUsing(function ($state, $record) {
                        $val = $record->attendance_status instanceof AttendanceStatus
                            ? $record->attendance_status->value
                            : (string) $record->attendance_status;

                        if ($val !== 'present') {
                            return '—';
                        }

                        return $state ? 'Lengkap' : 'Tidak Lengkap';
                    })
                    ->color(function ($state, $record) {
                        $val = $record->attendance_status instanceof AttendanceStatus
                            ? $record->attendance_status->value
                            : (string) $record->attendance_status;

                        if ($val !== 'present') {
                            return 'gray';
                        }

                        return $state ? 'success' : 'danger';
                    }),

                // ── Tanda Tangan (legacy — tersimpan dari AppSheet, tidak dikumpulkan Filament) ──
                TextColumn::make('signature_path')
                    ->label('TTD')
                    ->state(fn ($record) => $record->signature_path ? '✓' : '—')
                    ->color(fn ($record) => $record->signature_path ? 'success' : 'gray')
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),

                // ── Keluhan ────────────────────────────────────────────────
                TextColumn::make('health_complaint')
                    ->label('Keluhan')
                    ->limit(25)
                    ->tooltip(fn ($record) => $record->health_complaint)
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

            ])
            ->filters([

                Filter::make('present')
                    ->label('Hadir')
                    ->query(fn (Builder $q) => $q->where('attendance_status', AttendanceStatus::Present->value)),

                Filter::make('absent')
                    ->label('Tidak Hadir')
                    ->query(fn (Builder $q) => $q->where('attendance_status', AttendanceStatus::Absent->value)),

                Filter::make('sick')
                    ->label('Sakit')
                    ->query(fn (Builder $q) => $q->where('attendance_status', AttendanceStatus::Sick->value)),

                Filter::make('perlu_tindakan')
                    ->label('Perlu Tindakan')
                    ->query(
                        fn (Builder $q) => $q
                            ->where('attendance_status', AttendanceStatus::Present->value)
                            ->where(fn ($inner) => $inner
                                ->where('has_ppe', false)
                                ->orWhereNull('has_ppe')
                                ->orWhereNull('fit_status')
                                ->orWhere('fit_status', 'TIDAK FIT')
                                ->orWhere('recheck_required', true)
                                ->orWhereNotNull('medical_action')
                            )
                    ),

            ])
            ->headerActions([

                // ── Tambah MP Reguler ──────────────────────────────────────
                Tables\Actions\CreateAction::make()
                    ->label('Tambah MP')
                    ->mutateFormDataUsing(function (array $data) {
                        $data['session_id'] = $this->getOwnerRecord()->id;
                        $data['mp_type']    = 'regular';  // selalu reguler via form ini

                        return $data;
                    }),

                // ── Tambah Backup MP ───────────────────────────────────────
                Tables\Actions\Action::make('addBackup')
                    ->label('Tambah Backup MP')
                    ->icon('heroicon-o-user-plus')
                    ->color('warning')
                    ->modalHeading('Tambah Backup MP')
                    ->modalDescription(
                        'Backup MP adalah tenaga pengganti yang tidak terdaftar di roster depot.'
                    )
                    ->form(fn () => [
                        $this->dataBackupSection(),
                        $this->makePemeriksaanSection(),
                        $this->catatanSection(),
                    ])
                    ->action(function (array $data): void {
                        $session = $this->getOwnerRecord();

                        BriefingAttendance::create([
                            ...$data,
                            'session_id'  => $session->id,
                            'manpower_id' => null,   // backup tidak punya FK ke manpower
                            'mp_type'     => 'backup',
                        ]);

                        Notification::make()
                            ->title('Backup MP ditambahkan')
                            ->success()
                            ->send();
                    }),

                // ── Generate Semua MP Depot ────────────────────────────────
                Tables\Actions\Action::make('generateAll')
                    ->label('Generate Semua MP Depot')
                    ->icon('heroicon-o-sparkles')
                    ->requiresConfirmation()
                    ->modalDescription('Buat absensi untuk semua MP aktif di depot ini?')
                    ->action(function (): void {
                        $session = $this->getOwnerRecord();

                        $mpIds = Manpower::query()
                            ->where('depot_id', $session->depot_id)
                            ->where('active', true)
                            ->pluck('id');

                        $created = 0;
                        foreach ($mpIds as $mpId) {
                            $attendance = BriefingAttendance::firstOrCreate(
                                [
                                    'session_id'  => $session->id,
                                    'manpower_id' => $mpId,
                                ],
                                [
                                    'attendance_status' => AttendanceStatus::Present->value,
                                    'mp_type'           => 'regular',   // eksplisit reguler
                                    'has_ppe'           => true,
                                ]
                            );

                            if ($attendance->wasRecentlyCreated) {
                                $created++;
                            }
                        }

                        Notification::make()
                            ->title("{$created} MP ditambahkan")
                            ->success()
                            ->send();
                    }),

            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('Ubah'),

                // ── Tindakan Medis — muncul saat TIDAK FIT & belum ada tindakan ──
                Tables\Actions\Action::make('tindakanMedis')
                    ->label('Tindakan Medis')
                    ->icon('heroicon-o-heart')
                    ->color('warning')
                    ->visible(fn (BriefingAttendance $record): bool => (function () use ($record) {
                        $attVal = $record->attendance_status instanceof AttendanceStatus
                            ? $record->attendance_status->value
                            : (string) $record->attendance_status;

                        return $attVal === 'present'
                            && strtoupper((string) $record->fit_status) === 'TIDAK FIT'
                            && blank($record->medical_action);
                    })())
                    ->modalHeading('Tindakan Medis')
                    ->modalDescription('Pilih tindakan yang diambil untuk MP ini.')
                    ->modalWidth('md')
                    ->form([
                        Select::make('medical_action')
                            ->label('Tindakan')
                            ->options([
                                'Istirahat 30 menit' => 'Istirahat 30 Menit',
                                'Berobat'            => 'Berobat (ke faskes)',
                                'Pulang'             => 'Dipulangkan',
                            ])
                            ->required()
                            ->native(false),

                        Textarea::make('remark')
                            ->label('Catatan')
                            ->rows(2)
                            ->maxLength(500)
                            ->placeholder('Catatan tambahan (opsional)'),
                    ])
                    ->action(function (BriefingAttendance $record, array $data): void {
                        $update = ['medical_action' => $data['medical_action']];

                        if (! blank($data['remark'] ?? null)) {
                            $update['remark'] = $data['remark'];
                        }

                        if ($data['medical_action'] === 'Istirahat 30 menit') {
                            $update['recheck_required'] = true;
                            $update['rest_started_at']  = now();
                        } else {
                            // Berobat / Pulang — terminal, tidak ada recheck
                            $update['recheck_required'] = false;
                        }

                        $record->fill($update)->save();

                        AttendanceHealthLog::create([
                            'attendance_id'  => $record->id,
                            'event_type'     => 'medical_action',
                            'medical_action' => $data['medical_action'],
                            'remark'         => $data['remark'] ?? null,
                            'created_by'     => auth()->id(),
                        ]);

                        Notification::make()
                            ->title('Tindakan medis dicatat')
                            ->success()
                            ->send();
                    }),

                // ── Hasil Recheck — vitals-based, auto-evaluated ──────────────
                Tables\Actions\Action::make('hasilRecheck')
                    ->label('Hasil Recheck')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->color('info')
                    ->visible(fn (BriefingAttendance $record): bool =>
                        $record->recheck_required === true
                        && blank($record->recheck_result)
                    )
                    ->modalHeading('Pemeriksaan Ulang (Recheck)')
                    ->modalDescription('Input vital tanda-tanda kesehatan setelah istirahat. Sistem akan mengevaluasi otomatis.')
                    ->modalWidth('md')
                    ->form([
                        TextInput::make('recheck_temperature')
                            ->label('Suhu Recheck')
                            ->numeric()
                            ->minValue(35)
                            ->maxValue(42)
                            ->step(0.1)
                            ->suffix('°C')
                            ->helperText('Normal: 35.5 – 37.2 °C')
                            ->required(),

                        TextInput::make('recheck_bp_systolic')
                            ->label('TD Sistolik Recheck')
                            ->numeric()
                            ->minValue(60)
                            ->maxValue(250)
                            ->suffix('mmHg')
                            ->helperText('Normal: 90 – 120 mmHg')
                            ->required(),

                        TextInput::make('recheck_bp_diastolic')
                            ->label('TD Diastolik Recheck')
                            ->numeric()
                            ->minValue(40)
                            ->maxValue(150)
                            ->suffix('mmHg')
                            ->helperText('Normal: 60 – 80 mmHg')
                            ->required(),

                        Textarea::make('remark')
                            ->label('Catatan')
                            ->rows(2)
                            ->maxLength(500)
                            ->placeholder('Catatan hasil recheck (opsional)'),
                    ])
                    ->action(function (BriefingAttendance $record, array $data): void {
                        $temp = (float) $data['recheck_temperature'];
                        $sys  = (int)   $data['recheck_bp_systolic'];
                        $dia  = (int)   $data['recheck_bp_diastolic'];

                        $recheckResult = (
                            $temp >= 35.5 && $temp <= 37.2 &&
                            $sys  >= 90   && $sys  <= 120  &&
                            $dia  >= 60   && $dia  <= 80
                        ) ? 'FIT' : 'TIDAK FIT';

                        $record->fill([
                            'recheck_temperature'  => $temp,
                            'recheck_bp_systolic'  => $sys,
                            'recheck_bp_diastolic' => $dia,
                            'recheck_result'       => $recheckResult,
                            'recheck_at'           => now(),
                            'remark'               => $data['remark'] ?? $record->remark,
                        ])->save();

                        AttendanceHealthLog::create([
                            'attendance_id' => $record->id,
                            'event_type'    => $recheckResult === 'FIT' ? 'recheck_fit' : 'recheck_not_fit',
                            'temperature'   => $temp,
                            'bp_systolic'   => $sys,
                            'bp_diastolic'  => $dia,
                            'remark'        => $data['remark'] ?? null,
                            'created_by'    => auth()->id(),
                        ]);

                        $label = $recheckResult === 'FIT' ? 'FIT — Siap Kerja' : 'TIDAK FIT';

                        Notification::make()
                            ->title("Recheck selesai: {$label}")
                            ->color($recheckResult === 'FIT' ? 'success' : 'danger')
                            ->send();
                    }),

                Tables\Actions\DeleteAction::make()->label('Hapus'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->label('Hapus Terpilih'),
                ]),
            ]);
    }
}
