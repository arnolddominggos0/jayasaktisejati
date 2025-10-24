<?php

namespace App\Filament\Resources\ShippingScheduleResource\RelationManagers;

<<<<<<< HEAD
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Resources\RelationManagers\RelationManager;
=======
use App\Enums\ScheduleState;
use App\Models\ShippingLine;
use App\Models\ShippingSchedule;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Carbon;
>>>>>>> 1dcaff98d6e0ae89c5b689574805eed309eb1f47

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';
<<<<<<< HEAD
    protected static ?string $title = 'Detail Jadwal (TAM)';
    protected static ?string $recordTitleAttribute = 'vessel_name';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\DateTimePicker::make('etd')->label('ETD')->required(),
            Forms\Components\DateTimePicker::make('eta')->label('ETA')->required(),
            Forms\Components\TextInput::make('cargo_plan')->label('Cargo Plan')->numeric()->minValue(0)->nullable(),
            Forms\Components\TextInput::make('vessel_name')->label('Vessel')->maxLength(120)->required(),
            Forms\Components\TextInput::make('vessel_capacity')->label('Capacity')->numeric()->nullable(),
            Forms\Components\TextInput::make('voyage_no')->label('Voyage No')->maxLength(50)->nullable(),
            Forms\Components\TextInput::make('jss')->label('JSS')->maxLength(50)->nullable(),
            Forms\Components\TextInput::make('lts')->label('LTS')->maxLength(50)->nullable(),
            Forms\Components\TextInput::make('dwelling')->label('Dwelling')->numeric()->nullable(),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('etd')->label('ETD')->dateTime('d M'),
                Tables\Columns\TextColumn::make('eta')->label('ETA')->dateTime('d M'),
                Tables\Columns\TextColumn::make('cargo_plan')->label('Plan')->alignCenter(),
                Tables\Columns\TextColumn::make('vessel_name')->label('Vessel')->searchable(),
                Tables\Columns\TextColumn::make('vessel_capacity')->label('Cap')->alignCenter()->toggleable(),
                Tables\Columns\TextColumn::make('voyage_no')->label('Voy')->toggleable(),
                Tables\Columns\TextColumn::make('jss')->label('JSS')->toggleable(),
                Tables\Columns\TextColumn::make('lts')->label('LTS')->toggleable(),
                Tables\Columns\TextColumn::make('dwelling')->label('Dw')->alignCenter()->toggleable(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
=======
    protected static ?string $title = 'Baris Jadwal';
    protected static ?string $recordTitleAttribute = 'voyage_no';

    public function table(Table $table): Table
    {
        $parent = $this->getOwnerRecord();
        $isDraft = $parent instanceof ShippingSchedule
            ? ($parent->state === ScheduleState::Draft->value)
            : false;

        return $table
            ->columns([
                TextColumn::make('etd')->label('ETD')->dateTime(),
                TextColumn::make('eta')->label('ETA')->dateTime(),
                TextColumn::make('shippingLine.name')->label('Line'),
                TextColumn::make('vessel.name')->label('Vessel'),
                TextColumn::make('vessel_capacity')->label('Capacity')->state(fn($record) => $record->vessel_capacity),
                TextColumn::make('voyage_no')->label('Voyage No'),
                TextColumn::make('jss')->label('JSS')->state(fn($record) => $record->jss),
                TextColumn::make('dwelling')->label('Dwelling')->state(fn($record) => $record->dwelling),
                TextColumn::make('pol.code')->label('POL')->badge(),
                TextColumn::make('pod.code')->label('POD')->badge(),
                TextColumn::make('service')->label('Service')->badge(),
                TextColumn::make('cargo_plan')->label('Cargo Plan')->state(fn($record) => $record->cargo_plan),
            ])
            ->defaultSort('etd', 'asc')
            ->paginated(false)
            ->headerActions([
                Action::make('import_items_draft')
                    ->label('Import Baris (Draft)')
                    ->icon('heroicon-o-cloud-arrow-down')
                    ->visible($isDraft)
                    ->form([
                        Select::make('shipping_line_id')
                            ->label('Pelayaran')
                            ->options(fn() => \App\Models\ShippingLine::query()->orderBy('name')->pluck('name', 'id'))
                            ->required()
                            ->preload()
                            ->searchable(),
                        Textarea::make('paste_table')
                            ->label('Tempel Tabel')
                            ->rows(10)
                            ->required(),
                        TextInput::make('default_service')->label('Service default'),
                    ])
                    ->action(function (array $data) use ($parent) {
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
                            ];
                            return $map[$h] ?? $h;
                        };

                        $delimiter = $detectDelimiter($text);
                        $lines = preg_split("/\r\n|\n|\r/", $text);
                        $lines = array_values(array_filter($lines, fn($l) => trim($l) !== ''));
                        $headersRaw = str_getcsv(array_shift($lines), $delimiter);
                        $headers = array_map($normalizeHeader, $headersRaw);

                        $rows = [];
                        foreach ($lines as $line) {
                            $cols = str_getcsv($line, $delimiter);
                            $assoc = [];
                            foreach ($headers as $i => $key) {
                                $assoc[$key] = $cols[$i] ?? null;
                            }
                            $rows[] = $assoc;
                        }

                        $parseDate = function (?string $value) use ($parent): ?Carbon {
                            $value = trim((string)$value);
                            if ($value === '') return null;
                            $fmts = ['Y-m-d H:i', 'Y-m-d', 'd/m/Y H:i', 'd/m/Y', 'd-M', 'd/m', 'd M'];
                            foreach ($fmts as $f) {
                                try {
                                    $dt = Carbon::createFromFormat($f, $value);
                                    if ($dt !== false) {
                                        if (!str_contains($f, 'Y')) {
                                            $year = now()->year;
                                            if (preg_match('/^\d{4}\-\d{2}$/', (string)$parent->period_ym)) {
                                                [$y] = explode('-', $parent->period_ym);
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

                        $created = 0;
                        foreach ($rows as $r) {
                            $etd = $parseDate($r['etd'] ?? null);
                            $eta = $parseDate($r['eta'] ?? null);

                            $service = trim((string)($r['service'] ?? ''));
                            if ($service === '' && !empty($data['default_service'])) {
                                $service = (string)$data['default_service'];
                            }

                            $extra = array_filter([
                                'cargo_plan'       => $r['cargo_plan'] ?? null,
                                'capacity'         => $r['capacity'] ?? null,
                                'vessel_capacity'  => $r['vessel_capacity'] ?? null,
                                'dwelling'         => $r['dwelling'] ?? null,
                                'jss'              => $r['jss'] ?? null,
                            ], fn($v) => $v !== null && $v !== '');

                            $parent->items()->updateOrCreate(
                                [
                                    'vessel_id' => null,
                                    'voyage_no' => !empty($r['voyage_no']) ? $r['voyage_no'] : null,
                                    'pol_id'    => $parent->pol_id,
                                    'pod_id'    => $parent->pod_id,
                                    'etd'       => $etd?->toDateTimeString(),
                                ],
                                [
                                    'shipping_line_id' => (int)$data['shipping_line_id'],
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
>>>>>>> 1dcaff98d6e0ae89c5b689574805eed309eb1f47
            ]);
    }
}
