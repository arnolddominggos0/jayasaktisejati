<?php

namespace App\Filament\Resources;

use App\Enums\ScheduleState;
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
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Tables\Filters\SelectFilter;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Carbon;

class ShippingScheduleResource extends Resource
{
    protected static ?string $model = ShippingSchedule::class;
    protected static ?string $navigationGroup = 'Pelayaran & Kapal';
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
    }

    public static function table(Table $table): Table
    {
        return $table
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
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [ItemsRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index'   => ListShippingSchedules::route('/'),
            'create'  => CreateShippingSchedule::route('/create'),
            'edit'    => EditShippingSchedule::route('/{record}/edit'),
            'preview' => PreviewShippingSchedule::route('/{record}/preview'),
        ];
    }
}
