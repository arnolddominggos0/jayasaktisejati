<?php

namespace App\Filament\Resources;

use App\Enums\ScheduleState;
<<<<<<< HEAD
use App\Filament\Resources\ShippingScheduleResource\Pages;
use App\Filament\Resources\ShippingScheduleResource\RelationManagers\ItemsRelationManager;
use App\Models\ShippingSchedule;
use Filament\Forms;
use Filament\Forms\Form;
=======
use App\Filament\Resources\ShippingScheduleResource\Pages\CreateShippingSchedule;
use App\Filament\Resources\ShippingScheduleResource\Pages\EditShippingSchedule;
use App\Filament\Resources\ShippingScheduleResource\Pages\ListShippingSchedules;
use App\Filament\Resources\ShippingScheduleResource\Pages\PreviewShippingSchedule;
use App\Filament\Resources\ShippingScheduleResource\RelationManagers\ItemsRelationManager;
use App\Models\Customer;
use App\Models\Port;
use App\Models\ShippingLine;
use App\Models\ShippingSchedule;
use App\Models\Vessel;
use App\Supports\ScheduleExport;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Get;
>>>>>>> 1dcaff98d6e0ae89c5b689574805eed309eb1f47
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Tables\Filters\SelectFilter;
use Filament\Notifications\Notification;
<<<<<<< HEAD
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
=======
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;
>>>>>>> 1dcaff98d6e0ae89c5b689574805eed309eb1f47
use Illuminate\Support\Carbon;

class ShippingScheduleResource extends Resource
{
    protected static ?string $model = ShippingSchedule::class;
    protected static ?string $navigationGroup = 'Pelayaran & Kapal';
<<<<<<< HEAD
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationLabel = 'Jadwal Kapal (TAM)';
    protected static ?string $pluralLabel = 'Jadwal Kapal (TAM)';
    protected static ?string $slug = 'shipping-schedules';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Informasi Jadwal')
                ->schema([
                    TextInput::make('code')
                        ->label('Kode Jadwal')
                        ->maxLength(50)
                        ->unique(ignoreRecord: true),

                    Select::make('state')
                        ->label('Status')
                        ->options(ScheduleState::options())
                        ->disabled(),

                    DatePicker::make('period_month')
                        ->label('Periode Bulan')
                        ->displayFormat('F Y')
                        ->native(false)
                        ->helperText('Dipakai untuk arsip tahunan per bulan (ambil hari pertama bulan).')
                        ->default(now()->startOfMonth()),

                    TextInput::make('vessel_name')->label('Nama Kapal')->maxLength(120)->nullable(),
                    TextInput::make('voyage_no')->label('Voyage No')->maxLength(50)->nullable(),
                    DateTimePicker::make('etd')->label('ETD')->nullable(),
                    DateTimePicker::make('eta')->label('ETA')->nullable(),
                    TextInput::make('cargo_plan_total')->label('Cargo Plan')->numeric()->minValue(0)->nullable(),
                ])->columns(2),

            Section::make('Finalisasi TAM')
                ->schema([
                    TextInput::make('approved_by_name')->label('Disetujui oleh (TAM)')->disabled(),
                    Textarea::make('final_note')->label('Catatan Final (TAM)')->disabled()->rows(3),
                    TextInput::make('final_source')->label('Sumber Final')->disabled(),
                    DateTimePicker::make('approved_at')->label('Tanggal Disetujui')->disabled(),
                    TextInput::make('final_attachment_path')->label('Lampiran')->disabled(),
                    TextInput::make('final_email_from')->label('Email From')->disabled(),
                    TextInput::make('final_email_subject')->label('Subject Email')->disabled(),
                    DateTimePicker::make('final_email_received_at')->label('Diterima Pada')->disabled(),
                ])->columns(2),
        ])->columns(1);
=======
    protected static ?string $navigationLabel = 'Paket Jadwal Bulanan';
    protected static ?string $navigationIcon = 'heroicon-m-clipboard-document-list';
    protected static ?int $navigationSort = 9;

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            TextInput::make('title')
                ->label('Judul')
                ->maxLength(150)
                ->placeholder('Jadwal Kapal TAM ' . now()->format('F Y')),

            TextInput::make('period_ym')
                ->label('Periode (YYYY-MM)')
                ->required()
                ->default(fn() => now()->format('Y-m'))
                ->rule('regex:/^\d{4}\-\d{2}$/')
                ->helperText('Contoh: ' . now()->format('Y-m')),

            Select::make('customer_id')
                ->relationship('customer', 'name')
                ->label('Customer')
                ->required()
                ->preload()
                ->searchable()
                ->default(function () {
                    return Customer::query()
                        ->where('name', 'like', '%Toyota Astra Motor%')
                        ->orWhere('name', 'like', '%TAM%')
                        ->value('id');
                }),

            Select::make('pol_id')
                ->relationship('pol', 'name')
                ->label('POL')
                ->required()
                ->preload()
                ->searchable()
                ->default(function () {
                    return Port::query()
                        ->where('code', 'IDTPP')
                        ->orWhere('name', 'like', '%Tanjung Priok%')
                        ->value('id');
                })
                ->helperText('Fokus rute Jakarta → Manado dulu (KPI TAM).'),

            Select::make('pod_id')
                ->relationship('pod', 'name')
                ->label('POD')
                ->required()
                ->preload()
                ->searchable()
                ->default(function () {
                    return Port::query()
                        ->where('code', 'IDBIT')
                        ->orWhere('name', 'like', '%Bitung%')
                        ->orWhere('name', 'like', '%Manado%')
                        ->value('id');
                })
                ->helperText('Kunci ke Manado/Bitung untuk sementara.'),

            Textarea::make('notes')
                ->label('Catatan')
                ->rows(3),
        ])->columns(2);
>>>>>>> 1dcaff98d6e0ae89c5b689574805eed309eb1f47
    }

    public static function table(Table $table): Table
    {
        return $table
<<<<<<< HEAD
            ->columns([
                Tables\Columns\TextColumn::make('code')->label('Kode')->searchable()->sortable(),

                Tables\Columns\TextColumn::make('state')
                    ->label('Status')
                    ->state(fn($record) => $record->state instanceof ScheduleState ? $record->state->value : $record->state)
                    ->badge()
                    ->colors([
                        'warning' => 'draft',
                        'success' => 'final',
                    ])
                    ->formatStateUsing(fn(string $state) => ScheduleState::options()[$state] ?? ucfirst($state)),

                Tables\Columns\TextColumn::make('period_month')
                    ->label('Periode')
                    ->formatStateUsing(fn($state) => $state ? Carbon::parse($state)->translatedFormat('F Y') : '-')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('vessel_name')->label('Kapal')->searchable(),
                Tables\Columns\TextColumn::make('voyage_no')->label('Voyage')->toggleable(),
                Tables\Columns\TextColumn::make('etd')->label('ETD')->dateTime('d M Y H:i'),
                Tables\Columns\TextColumn::make('eta')->label('ETA')->dateTime('d M Y H:i'),
                Tables\Columns\TextColumn::make('revision_count')->label('Rev')->sortable()->alignCenter(),
            ])

            ->filters([
                SelectFilter::make('year')
                    ->label('Tahun')
                    ->options(function () {
                        $years = ShippingSchedule::query()
                            ->whereNotNull('period_month')
                            ->selectRaw('DISTINCT EXTRACT(YEAR FROM period_month)::int AS y')
                            ->orderBy('y', 'desc')
                            ->pluck('y', 'y')
                            ->toArray();

                        return $years ?: [now()->year => (string) now()->year];
                    })
                    ->placeholder('Semua tahun')
                    ->query(fn($query, $state) => $query->when(
                        $state['value'] ?? null,
                        fn($qq, $y) => $qq->whereYear('period_month', (int) $y)
                    )),

                SelectFilter::make('month')
                    ->label('Bulan')
                    ->options(fn() => collect(range(1, 12))->mapWithKeys(
                        fn($m) => [$m => Carbon::create(null, $m, 1)->translatedFormat('F')]
                    )->toArray())
                    ->placeholder('Semua bulan')
                    ->query(fn($query, $state) => $query->when(
                        $state['value'] ?? null,
                        fn($qq, $m) => $qq->whereMonth('period_month', (int) $m)
                    )),
            ])

            ->actions([
                Action::make('final_from_email')
                    ->label('Final dari Email TAM')
                    ->icon('heroicon-o-check-circle')
                    ->visible(fn(ShippingSchedule $record) => $record->canFinalizeFromEmail())
                    ->form([
                        Forms\Components\TextInput::make('approved_by_name')
                            ->label('Disetujui oleh (TAM)')
                            ->required(),

                        DatePicker::make('period_month')
                            ->label('Periode Bulan')
                            ->displayFormat('F Y')
                            ->native(false)
                            ->default(now()->startOfMonth())
                            ->helperText('Pilih bulan arsip untuk jadwal ini.'),

                        Forms\Components\TextInput::make('email_from')->label('Email From')->placeholder('mis. tam-logistics@toyota.co.id'),
                        Forms\Components\TextInput::make('email_subject')->label('Subject Email'),
                        Forms\Components\DateTimePicker::make('email_received_at')->label('Diterima Pada')->default(now()),
                        Forms\Components\Textarea::make('final_note')->label('Catatan Final')->rows(4),

                        FileUpload::make('final_attachment')
                            ->label('Lampiran Email (PDF/Excel/IMG)')
                            ->directory('schedules/' . date('Y/m'))
                            ->disk('public')->visibility('public')
                            ->preserveFilenames()
                            ->acceptedFileTypes([
                                'application/pdf',
                                'application/vnd.ms-excel',
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                'image/*'
                            ])->maxSize(20 * 1024),

                        Forms\Components\Textarea::make('paste_table')
                            ->label('Tempel Tabel Final (opsional)')
                            ->rows(12)
                            ->helperText('Tempel tabel hijau dari email/Excel TAM (ETD, ETA, Cargo, Capacity, Vessel, Voyage, JSS, LTS, Dwelling).'),
                    ])
                    ->action(function (array $data, ShippingSchedule $record) {
                        $path = null;
                        if (!empty($data['final_attachment'])) {
                            $path = is_string($data['final_attachment'])
                                ? $data['final_attachment']
                                : (is_array($data['final_attachment']) ? ($data['final_attachment'][0] ?? null) : null);
                        }

                        $record->finalizeFromEmail([
                            'approved_by_name'      => $data['approved_by_name'] ?? null,
                            'final_note'            => $data['final_note'] ?? null,
                            'final_attachment_path' => $path,
                            'approved_at'           => now(),
                        ]);

                        // set arsip bulan
                        if (!empty($data['period_month'])) {
                            $record->period_month = Carbon::parse($data['period_month'])->startOfMonth();
                        } elseif (!$record->period_month) {
                            $base = $record->etd ?: $record->approved_at ?: now();
                            $record->period_month = Carbon::parse($base)->startOfMonth();
                        }

                        $record->setEmailFinalMeta([
                            'message_id' => null,
                            'from'       => $data['email_from'] ?? null,
                            'subject'    => $data['email_subject'] ?? null,
                            'received_at' => $data['email_received_at'] ?? now(),
                        ]);
                        $record->save();

                        if (!empty($data['paste_table'])) {
                            $rows = \App\Support\TamFinalTableParser::parse($data['paste_table']);

                            // regenerate items penuh sesuai paste
                            $record->items()->delete();
                            foreach ($rows as $r) {
                                $record->items()->create([
                                    'etd'             => $r['etd'],
                                    'eta'             => $r['eta'],
                                    'cargo_plan'      => $r['cargo_plan'],
                                    'vessel_name'     => $r['vessel_name'],
                                    'vessel_capacity' => $r['vessel_capacity'],
                                    'voyage_no'       => $r['voyage_no'],
                                    'jss'             => $r['jss'],
                                    'lts'             => $r['lts'],
                                    'dwelling'        => $r['dwelling'],
                                ]);
                            }

                            // sinkron ringkasan dari baris pertama jika kosong
                            if (!empty($rows)) {
                                $first = $rows[0];
                                $record->etd         = $record->etd         ?: ($first['etd'] ?? null);
                                $record->eta         = $record->eta         ?: ($first['eta'] ?? null);
                                $record->vessel_name = $record->vessel_name ?: ($first['vessel_name'] ?? null);
                                $record->voyage_no   = $record->voyage_no   ?: ($first['voyage_no'] ?? null);
                                $record->cargo_plan_total = $record->items()->sum('cargo_plan');
                                $record->save();
                            }
                        }

                        Notification::make()
                            ->title('Final diterapkan dari Email TAM. Data tersimpan.')
                            ->success()
                            ->send();
                    }),

                Action::make('limited_revision')
                    ->label('Revisi Terbatas (≤ +6 hari)')
                    ->icon('heroicon-o-pencil-square')
                    ->requiresConfirmation()
                    ->visible(fn(ShippingSchedule $record) => $record->withinRevisionWindow())
                    ->form([
                        Forms\Components\Placeholder::make('deadline_info')->content(function (ShippingSchedule $record) {
                            $dl = $record->revisionDeadline();
                            return $dl ? 'Batas revisi sampai: ' . $dl->format('d M Y H:i') : 'Batas revisi tidak tersedia';
                        }),
                        TextInput::make('vessel_name')->label('Nama Kapal')->maxLength(120),
                        TextInput::make('voyage_no')->label('Voyage No')->maxLength(50),
                        DateTimePicker::make('etd')->label('ETD Baru'),
                        DateTimePicker::make('eta')->label('ETA Baru'),
                        TextInput::make('cargo_plan_total')->label('Cargo Plan')->numeric()->minValue(0),
                        Textarea::make('note')->label('Catatan Revisi')->rows(4),
                    ])
                    ->action(function (array $data, ShippingSchedule $record) {
                        $payload = collect($data)->only(['vessel_name', 'voyage_no', 'etd', 'eta', 'cargo_plan_total'])->toArray();

                        if (!empty($payload['etd']) && !empty($payload['eta'])) {
                            $etd = Carbon::parse($payload['etd']);
                            $eta = Carbon::parse($payload['eta']);
                            if ($etd->gte($eta)) {
                                Notification::make()->title('ETD harus lebih awal dari ETA')->danger()->send();
                                return;
                            }
                        }

                        try {
                            $record->applyLimitedRevision($payload);
                        } catch (\Throwable $e) {
                            Notification::make()->title('Revisi ditolak: ' . $e->getMessage())->danger()->send();
                            return;
                        }

                        Notification::make()
                            ->title('Revisi tersimpan. Status tetap Final.')
=======
            ->modifyQueryUsing(function (Builder $query) {
                $bitungIds = Port::query()
                    ->where('code', 'IDBIT')
                    ->orWhere('name', 'like', '%Bitung%')
                    ->orWhere('name', 'like', '%Manado%')
                    ->pluck('id')
                    ->all();

                if (!empty($bitungIds)) {
                    $query->whereIn('pod_id', $bitungIds);
                }
            })
            ->columns([
                Tables\Columns\TextColumn::make('period_ym')
                    ->label('Periode')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Customer')
                    ->wrap(),

                Tables\Columns\TextColumn::make('pol.code')
                    ->label('POL')
                    ->badge(),

                Tables\Columns\TextColumn::make('pod.code')
                    ->label('POD')
                    ->badge(),

                Tables\Columns\TextColumn::make('state')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn($state) => ucfirst($state))
                    ->colors([
                        'warning' => 'draft',
                        'success' => 'final',
                    ]),

                Tables\Columns\TextColumn::make('items_count')
                    ->counts('items')
                    ->label('Baris'),

                Tables\Columns\TextColumn::make('finalized_at')
                    ->label('Finalized')
                    ->since(),
            ])
            ->filters([
                SelectFilter::make('state')
                    ->label('Status')
                    ->options([
                        ScheduleState::Draft->value => 'Draft',
                        ScheduleState::Final->value => 'Final',
                    ])
                    ->default(ScheduleState::Draft->value),

                SelectFilter::make('pod_id')
                    ->label('POD')
                    ->relationship('pod', 'name')
                    ->default(function () {
                        return Port::query()
                            ->where('code', 'IDBIT')
                            ->orWhere('name', 'like', '%Bitung%')
                            ->orWhere('name', 'like', '%Manado%')
                            ->value('id');
                    })->preload()->searchable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),

                Action::make('import_from_line')
                    ->label('Import dari Pelayaran (Draft)')
                    ->icon('heroicon-o-cloud-arrow-down')
                    ->visible(fn(ShippingSchedule $record) => $record->state === ScheduleState::Draft->value)
                    ->form([
                        Select::make('shipping_line_id')
                            ->label('Pelayaran')
                            ->options(fn() => ShippingLine::query()
                                ->orderBy('name')
                                ->pluck('name', 'id'))
                            ->required()
                            ->preload()
                            ->searchable()
                            ->helperText('Contoh: Tanto Line, Meratus Line'),

                        TextInput::make('line_hint')
                            ->label('Alias/Hint Line')
                            ->placeholder('Tanto / Meratus (opsional)')
                            ->helperText('Dipakai kalau kolom line di tabel kosong.'),

                        Textarea::make('paste_table')
                            ->label('Tempel Tabel (copy dari Email/Excel)')
                            ->rows(12)
                            ->required()
                            ->helperText('Boleh pakai pemisah tab, koma, atau titik-koma.'),

                        TextInput::make('default_service')
                            ->label('Service default')
                            ->placeholder('Direct / Via Bitung / dll')
                            ->helperText('Jika kolom service di tabel kosong, pakai nilai ini.'),

                        TextInput::make('vessel_override')
                            ->label('Nama Kapal override')
                            ->placeholder('Kosongkan jika pakai dari tabel'),

                        TextInput::make('voyage_override')
                            ->label('Voyage No override')
                            ->placeholder('Kosongkan jika pakai dari tabel'),
                    ])
                    ->action(function (ShippingSchedule $record, array $data) {
                        $text = trim((string)($data['paste_table'] ?? ''));
                        if ($text === '') {
                            Notification::make()->title('Tidak ada data.')->warning()->send();
                            return;
                        }

                        $detectDelimiter = function (string $t): string {
                            $c = substr_count($t, ',');
                            $tcount = substr_count($t, "\t");
                            $s = substr_count($t, ';');
                            if ($tcount >= $c && $tcount >= $s) return "\t";
                            if ($c >= $tcount && $c >= $s) return ',';
                            return ';';
                        };

                        $normalizeHeader = function (string $h): string {
                            $h = strtolower(trim($h));
                            $h = str_replace(['  ', ' ', '-', '__'], [' ', '_', '_', '_'], $h);
                            $map = [
                                'shipping_line'   => 'line',
                                'line'            => 'line',
                                'vessel'          => 'vessel',
                                'vessel_name'     => 'vessel',
                                'kapal'           => 'vessel',
                                'voy'             => 'voyage_no',
                                'voyage'          => 'voyage_no',
                                'voyage_no'       => 'voyage_no',
                                'pol_code'        => 'pol',
                                'pod_code'        => 'pod',
                                'pol'             => 'pol',
                                'pod'             => 'pod',
                                'eta'             => 'eta',
                                'etd'             => 'etd',
                                'service'         => 'service',
                                'cargo_plan'      => 'cargo_plan',
                                'dwelling'        => 'dwelling',
                                'jss'             => 'jss',
                                'vessel_capacity' => 'vessel_capacity',
                                'capacity'        => 'capacity',
                                'direct'          => 'direct',
                                'transit'         => 'transit',
                            ];
                            return $map[$h] ?? $h;
                        };

                        $delimiter = $detectDelimiter($text);
                        $lines = preg_split("/\r\n|\n|\r/", $text);
                        $lines = array_values(array_filter($lines, fn($l) => trim($l) !== ''));
                        $headersRaw = str_getcsv(array_shift($lines), $delimiter);
                        $headers = array_map($normalizeHeader, $headersRaw);

                        $seen = [];
                        foreach ($headers as $i => $key) {
                            if (!isset($seen[$key])) {
                                $seen[$key] = 1;
                            } else {
                                $seen[$key]++;
                                $headers[$i] = $key . '_' . $seen[$key];
                            }
                        }

                        $rows = [];
                        foreach ($lines as $line) {
                            $cols = str_getcsv($line, $delimiter);
                            $assoc = [];
                            foreach ($headers as $i => $key) {
                                $assoc[$key] = $cols[$i] ?? null;
                            }
                            $rows[] = $assoc;
                        }

                        $created = 0;

                        foreach ($rows as $r) {
                            $vessel = trim((string)($data['vessel_override'] ?: ($r['vessel'] ?? '')));
                            $voy    = trim((string)($data['voyage_override'] ?: ($r['voyage_no'] ?? '')));
                            $etdStr = trim((string)($r['etd'] ?? ''));
                            if ($vessel === '' && $voy === '' && $etdStr === '') {
                                continue;
                            }

                            $shippingLineId = (int)($data['shipping_line_id']);
                            $polId = $record->pol_id;
                            $podId = $record->pod_id;

                            $parseDate = function (?string $value) use ($record): ?Carbon {
                                $value = trim((string)$value);
                                if ($value === '') return null;
                                $fmts = ['Y-m-d H:i', 'Y-m-d', 'd/m/Y H:i', 'd/m/Y', 'd-M', 'd/m', 'd M'];
                                foreach ($fmts as $f) {
                                    try {
                                        $dt = Carbon::createFromFormat($f, $value);
                                        if ($dt !== false) {
                                            if (!str_contains($f, 'Y')) {
                                                $year = now()->year;
                                                if (preg_match('/^\d{4}\-\d{2}$/', (string)$record->period_ym)) {
                                                    [$y] = explode('-', $record->period_ym);
                                                    $year = (int)$y;
                                                }
                                                return Carbon::create($year, $dt->month, $dt->day, $dt->hour, $dt->minute);
                                            }
                                            return $dt;
                                        }
                                    } catch (\Throwable $e) {
                                    }
                                }
                                $ts = strtotime($value);
                                return $ts ? Carbon::parse(date('Y-m-d H:i:s', $ts)) : null;
                            };

                            $etd = $parseDate($r['etd'] ?? null);
                            $eta = $parseDate($r['eta'] ?? null);

                            $service = trim((string)($r['service'] ?? ''));
                            if ($service === '' && !empty($data['default_service'])) {
                                $service = (string)$data['default_service'];
                            }

                            $vesselId = null;
                            if ($vessel !== '') {
                                $existVessel = Vessel::where('name', $vessel)->first();
                                if ($existVessel) $vesselId = $existVessel->id;
                            }

                            $extra = array_filter([
                                'cargo_plan'       => $r['cargo_plan'] ?? null,
                                'capacity'         => $r['capacity'] ?? null,
                                'vessel_capacity'  => $r['vessel_capacity'] ?? null,
                                'dwelling'         => $r['dwelling'] ?? null,
                                'jss'              => $r['jss'] ?? null,
                            ], fn($v) => $v !== null && $v !== '');

                            $record->items()->updateOrCreate(
                                [
                                    'vessel_id' => $vesselId,
                                    'voyage_no' => $voy !== '' ? $voy : null,
                                    'pol_id'    => $polId,
                                    'pod_id'    => $podId,
                                    'etd'       => $etd?->toDateTimeString(),
                                ],
                                [
                                    'shipping_line_id' => $shippingLineId,
                                    'eta'              => $eta?->toDateTimeString(),
                                    'service'          => $service ?: null,
                                    'extra'            => $extra,
                                ]
                            );

                            $created++;
                        }

                        Notification::make()
                            ->title("Import selesai: {$created} baris draft ditambahkan/diupdate")
                            ->success()
                            ->send();
                    }),

                Action::make('preview_draft')
                    ->label('Preview / Print')
                    ->icon('heroicon-o-document-text')
                    ->url(fn($record) => static::getUrl('preview', ['record' => $record]))
                    ->openUrlInNewTab(),

                Action::make('export_draft_csv')
                    ->label('Export CSV')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(function (ShippingSchedule $record) {
                        $csv = ScheduleExport::csv($record);
                        $filename = 'draft-jadwal-' . str_replace(' ', '-', strtolower($record->customer?->name ?? 'customer')) . '-' . $record->period_ym . '.csv';
                        return new StreamedResponse(function () use ($csv) {
                            echo $csv;
                        }, 200, [
                            'Content-Type' => 'text/csv',
                            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                        ]);
                    }),

                Action::make('log_final_from_customer')
                    ->label('Log Final dari Customer (Email/WA)')
                    ->icon('heroicon-o-check-circle')
                    ->visible(fn($record) => $record->state === ScheduleState::Draft->value)
                    ->form([
                        TextInput::make('approved_by_name')
                            ->label('Disetujui oleh')
                            ->maxLength(120),

                        TextInput::make('line_hint')
                            ->label('Line (opsional, jika tidak ada di tabel)')
                            ->placeholder('Tanto Line / Meratus / lainnya'),

                        Textarea::make('paste_table')
                            ->label('Tempel Tabel Final (copy dari Email / Excel)')
                            ->rows(12)
                            ->required(),

                        FileUpload::make('final_attachment')
                            ->label('Lampiran Email / Excel')
                            ->disk('public')
                            ->directory('schedules/' . date('Y/m'))
                            ->visibility('public'),

                        Textarea::make('final_note')->label('Catatan Final'),
                    ])
                    ->action(function (ShippingSchedule $record, array $data) {
                        $path = null;
                        if (!empty($data['final_attachment'])) {
                            $path = is_array($data['final_attachment'])
                                ? ($data['final_attachment'][0] ?? null)
                                : $data['final_attachment'];
                        }

                        $result = $record->finalizeFromWhatsapp(
                            (string) ($data['paste_table'] ?? ''),
                            $data['final_note'] ?? null,
                            $path,
                            $data['approved_by_name'] ?? null,
                            auth()->id(),
                            lineHint: $data['line_hint'] ?? null
                        );

                        Notification::make()
                            ->title('Final jadwal dicatat: ' . $result['items'] . ' baris')
>>>>>>> 1dcaff98d6e0ae89c5b689574805eed309eb1f47
                            ->success()
                            ->send();
                    }),
            ])
<<<<<<< HEAD
            ->bulkActions([])
            ->defaultSort('etd', 'asc');
=======
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
>>>>>>> 1dcaff98d6e0ae89c5b689574805eed309eb1f47
    }

    public static function getRelations(): array
    {
<<<<<<< HEAD
        return [
            ItemsRelationManager::class,
        ];
=======
        return [ItemsRelationManager::class];
>>>>>>> 1dcaff98d6e0ae89c5b689574805eed309eb1f47
    }

    public static function getPages(): array
    {
        return [
<<<<<<< HEAD
            'index'  => Pages\ListShippingSchedules::route('/'),
            'create' => Pages\CreateShippingSchedule::route('/create'),
            'edit'   => Pages\EditShippingSchedule::route('/{record}/edit'),
=======
            'index'   => ListShippingSchedules::route('/'),
            'create'  => CreateShippingSchedule::route('/create'),
            'edit'    => EditShippingSchedule::route('/{record}/edit'),
            'preview' => PreviewShippingSchedule::route('/{record}/preview'),
>>>>>>> 1dcaff98d6e0ae89c5b689574805eed309eb1f47
        ];
    }
}
