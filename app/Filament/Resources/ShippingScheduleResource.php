<?php

namespace App\Filament\Resources;

use App\Enums\ScheduleState;
use App\Filament\Resources\ShippingScheduleResource\Pages;
use App\Models\ShippingSchedule;
use App\Models\Voyage;
use App\Models\ShippingLine;
use App\Models\Vessel;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\ToggleButtons;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class ShippingScheduleResource extends Resource
{
    protected static ?string $model = ShippingSchedule::class;

    protected static ?string $navigationGroup = 'Operasional Kapal';
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationLabel = 'Jadwal Pelayaran';
    protected static ?string $modelLabel = 'Shipping Schedule';
    protected static ?string $pluralModelLabel = 'Shipping Schedule';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Grid::make(12)->schema([
                Group::make()->schema([
                    Section::make('Info Voyage & Kapal')
                        ->schema([
                            Select::make('voyage_id')
                                ->label('Voyage')
                                ->native(false)
                                ->searchable()
                                ->preload()
                                ->relationship('voyage', 'voyage_no')
                                ->getOptionLabelUsing(function ($value) {
                                    $v = Voyage::with(['vessel', 'pol', 'pod'])->find($value);
                                    if (!$v) return null;

                                    $etd = $v->etd ? Carbon::parse($v->etd)->format('d M Y H:i') : '-';
                                    $pol = $v->pol?->code ?: $v->pol?->name ?: '-';
                                    $pod = $v->pod?->code ?: $v->pod?->name ?: '-';

                                    return sprintf(
                                        '%s / %s — %s (%s → %s)',
                                        $v->vessel?->name ?: '-',
                                        $v->voyage_no,
                                        $etd,
                                        $pol,
                                        $pod,
                                    );
                                })
                                ->getSearchResultsUsing(function (string $search) {
                                    return Voyage::with(['vessel', 'pol', 'pod'])
                                        ->when($search !== '', function ($q) use ($search) {
                                            $q->where('voyage_no', 'ilike', "%{$search}%")
                                                ->orWhereHas('vessel', fn($q2) => $q2->where('name', 'ilike', "%{$search}%"))
                                                ->orWhereHas('pol', fn($q2) => $q2
                                                    ->where('code', 'ilike', "%{$search}%")
                                                    ->orWhere('name', 'ilike', "%{$search}%"))
                                                ->orWhereHas('pod', fn($q2) => $q2
                                                    ->where('code', 'ilike', "%{$search}%")
                                                    ->orWhere('name', 'ilike', "%{$search}%"));
                                        })
                                        ->orderByDesc('etd')
                                        ->limit(50)
                                        ->get()
                                        ->mapWithKeys(function ($v) {
                                            $etd = $v->etd ? Carbon::parse($v->etd)->format('d M Y H:i') : '-';
                                            $pol = $v->pol?->code ?: $v->pol?->name ?: '-';
                                            $pod = $v->pod?->code ?: $v->pod?->name ?: '-';

                                            return [
                                                $v->id => sprintf(
                                                    '%s / %s — %s (%s → %s)',
                                                    $v->vessel?->name ?: '-',
                                                    $v->voyage_no,
                                                    $etd,
                                                    $pol,
                                                    $pod,
                                                ),
                                            ];
                                        })
                                        ->toArray();
                                })
                                ->required()
                                ->live()
                                ->afterStateUpdated(function ($state, callable $set) {
                                    if (!$state) {
                                        $set('shipping_line_id', null);
                                        $set('vessel_id', null);
                                        $set('vessel_name', null);
                                        $set('voyage_no', null);
                                        $set('etd', null);
                                        $set('eta', null);
                                        $set('period_month', null);
                                        return;
                                    }

                                    $v = Voyage::with(['vessel.shippingLine'])->find($state);
                                    if (!$v) return;

                                    $set('vessel_id', $v->vessel_id);
                                    $set('vessel_name', $v->vessel?->name);
                                    $set('voyage_no', $v->voyage_no);
                                    $set('etd', $v->etd?->toDateTimeString());
                                    $set('eta', $v->eta?->toDateTimeString());
                                    $set('shipping_line_id', $v->vessel?->shipping_line_id);

                                    if ($v->etd) {
                                        $set('period_month', $v->etd->copy()->startOfMonth()->toDateString());
                                    }
                                }),

                            Select::make('shipping_line_id')
                                ->label('Shipping Line')
                                ->relationship('shippingLine', 'name')
                                ->searchable()
                                ->preload()
                                ->native(false),

                            Select::make('vessel_id')
                                ->label('Kapal')
                                ->relationship('vessel', 'name')
                                ->searchable()
                                ->preload()
                                ->native(false),

                            TextInput::make('vessel_name')
                                ->label('Nama Kapal (override)')
                                ->maxLength(100),

                            TextInput::make('voyage_no')
                                ->label('Voyage No')
                                ->maxLength(50)
                                ->required(),
                        ])
                        ->columns(2),

                    Section::make('Periode & KPI')
                        ->schema([
                            DateTimePicker::make('etd')
                                ->label('ETD')
                                ->seconds(false)
                                ->required(),

                            DateTimePicker::make('eta')
                                ->label('ETA')
                                ->seconds(false)
                                ->required(),

                            DatePicker::make('period_month')
                                ->label('Periode (bulan)')
                                ->displayFormat('M Y')
                                ->native(false)
                                ->required(),

                            TextInput::make('cargo_plan')
                                ->label('Rencana Muatan (unit)')
                                ->numeric()
                                ->minValue(0),

                            TextInput::make('dwelling_days')
                                ->label('Target Dwelling (hari)')
                                ->numeric()
                                ->minValue(0),

                            TextInput::make('kpi_sailing_days')
                                ->label('Target Sailing (hari)')
                                ->numeric()
                                ->minValue(0),

                            TextInput::make('actual_sailing_days')
                                ->label('Actual Sailing (hari)')
                                ->numeric()
                                ->minValue(0)
                                ->disabled(),
                        ])
                        ->columns(3),
                ])->columnSpan(['default' => 12, 'lg' => 8]),

                Group::make()->schema([
                    Section::make('Status & Finalisasi')
                        ->schema([
                            ToggleButtons::make('state')
                                ->label('Status Schedule')
                                ->options(collect(ScheduleState::cases())->mapWithKeys(fn($c) => [$c->value => $c->label()]))
                                ->inline()
                                ->required(),

                            TextInput::make('jss')
                                ->label('Kode JSS')
                                ->disabled()
                                ->dehydrated(),

                            TextInput::make('approved_by_name')
                                ->label('Disetujui oleh')
                                ->maxLength(100),

                            DateTimePicker::make('finalized_at')
                                ->label('Tgl Final')
                                ->seconds(false),

                            TextInput::make('final_source')
                                ->label('Sumber Final')
                                ->maxLength(100),

                            TextInput::make('final_attachment_path')
                                ->label('Lampiran Final (path)')
                                ->maxLength(255),
                        ]),

                    Section::make('Catatan Akhir')
                        ->schema([
                            Textarea::make('final_note')
                                ->label('Catatan Final')
                                ->rows(5),
                        ]),

                    Section::make('Info Sistem')
                        ->schema([
                            Placeholder::make('info_generated')
                                ->label('Keterangan')
                                ->content(function (?ShippingSchedule $record) {
                                    if (!$record) {
                                        return 'JSS akan digenerate otomatis saat disimpan apabila voyage dan vessel sudah lengkap.';
                                    }

                                    return 'Kode JSS: ' . ($record->jss ?: 'Belum terbentuk otomatis.');
                                }),
                        ]),
                ])->columnSpan(['default' => 12, 'lg' => 4]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $query->with(['voyage.vessel.shippingLine', 'voyage.pol', 'voyage.pod']);
            })
            ->columns([
                TextColumn::make('jss')
                    ->label('Kode JSS')
                    ->badge()
                    ->sortable()
                    ->searchable(),

                TextColumn::make('voyage.vessel.shippingLine.name')
                    ->label('Pelayaran')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('voyage.vessel.name')
                    ->label('Kapal')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('voyage.voyage_no')
                    ->label('Voyage')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('voyage.pol.code')
                    ->label('POL')
                    ->sortable(),

                TextColumn::make('voyage.pod.code')
                    ->label('POD')
                    ->sortable(),

                TextColumn::make('etd')
                    ->label('ETD')
                    ->dateTime('d M Y')
                    ->sortable(),

                TextColumn::make('eta')
                    ->label('ETA')
                    ->dateTime('d M Y')
                    ->sortable(),

                TextColumn::make('cargo_plan')
                    ->label('Plan')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('actual_sailing_days')
                    ->label('Sailing (actual)')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('period_month')
                    ->label('Periode')
                    ->date('M Y')
                    ->sortable(),

                IconColumn::make('state')
                    ->label('Status')
                    ->boolean()
                    ->getStateUsing(fn(ShippingSchedule $r) => $r->state === ScheduleState::Final)
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-clock')
                    ->tooltip(fn(ShippingSchedule $r) => $r->state?->label() ?? '-'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('shipping_line_id')
                    ->label('Pelayaran')
                    ->relationship('shippingLine', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('state')
                    ->label('Status')
                    ->options(collect(ScheduleState::cases())->mapWithKeys(fn($c) => [$c->value => $c->label()])),
            ])
            ->defaultSort('etd', 'desc')
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListShippingSchedules::route('/'),
            'create' => Pages\CreateShippingSchedule::route('/create'),
            'edit'   => Pages\EditShippingSchedule::route('/{record}/edit'),
        ];
    }
}
