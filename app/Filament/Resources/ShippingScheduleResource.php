<?php

namespace App\Filament\Resources;

use App\Models\ShippingLine;
use App\Models\ShippingSchedule;
use App\Models\Vessel;
use Filament\Forms;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\Filter;

class ShippingScheduleResource extends Resource
{
    protected static ?string $model = ShippingSchedule::class;
    protected static ?string $navigationGroup = 'Pelayaran & Kapal';
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationLabel = 'Jadwal Kapal (TAM)';
    protected static ?string $slug = 'shipping-schedules';

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Section::make('Informasi Kapal')
                ->columns(3)
                ->schema([
                    Select::make('shipping_line_id')
                        ->label('Shipping Line')
                        ->options(ShippingLine::query()->orderBy('name')->pluck('name', 'id'))
                        ->native(false)
                        ->searchable()
                        ->preload()
                        ->required()
                        ->live(),
                    Select::make('vessel_id')
                        ->label('Nama Kapal')
                        ->options(function (Get $get) {
                            $lineId = $get('shipping_line_id');
                            return Vessel::query()
                                ->when($lineId, fn($q) => $q->where('shipping_line_id', $lineId))
                                ->orderBy('name')
                                ->pluck('name', 'id');
                        })
                        ->native(false)
                        ->searchable()
                        ->preload()
                        ->required()
                        ->live()
                        ->afterStateUpdated(function (Set $set, $state) {
                            $name = Vessel::query()->whereKey($state)->value('name');
                            $set('vessel_name', $name);
                        }),
                    TextInput::make('voyage_no')->label('Voyage No')->maxLength(40),
                ]),
            Section::make('Waktu & Rencana')
                ->columns(3)
                ->schema([
                    DateTimePicker::make('etd')->label('ETD')->seconds(false)->native(false)->required(),
                    DateTimePicker::make('eta')->label('ETA')->seconds(false)->native(false)->required(),
                    TextInput::make('cargo_plan_total')->label('Cargo Plan')->numeric()->default(0),
                ]),
            Section::make('Finalisasi (opsional)')
                ->columns(2)
                ->schema([
                    TextInput::make('approved_by_name')->label('Disetujui oleh')->maxLength(120),
                    TextInput::make('final_note')->label('Catatan Final')->maxLength(1000),
                ]),
            TextInput::make('vessel_name')->label('Nama Kapal (Snapshot)')->disabled()->dehydrated(true),
        ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('shippingLine.name')->label('Shipping Line')->badge()->sortable()->searchable(),
                Tables\Columns\TextColumn::make('vessel_name')->label('Kapal')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('voyage_no')->label('Voyage')->searchable(),
                Tables\Columns\TextColumn::make('etd')->label('ETD')->dateTime('d M Y H:i')->sortable(),
                Tables\Columns\TextColumn::make('eta')->label('ETA')->dateTime('d M Y H:i')->sortable(),
                Tables\Columns\TextColumn::make('cargo_plan_total')->label('Cargo Plan')->numeric(),
                Tables\Columns\BadgeColumn::make('state')->label('Status')->colors([
                    'warning' => 'draft',
                    'success' => 'final',
                ])->sortable(),
                Tables\Columns\TextColumn::make('updated_at')->since()->label('Diubah'),
            ])
            ->filters([
                Filter::make('bulan')
                    ->form([
                        Forms\Components\Select::make('month')->label('Bulan')->options(collect(range(1, 12))->mapWithKeys(fn($m) => [$m => date('F', mktime(0, 0, 0, $m, 1))])->all()),
                        Forms\Components\TextInput::make('year')->label('Tahun')->numeric()->default(now()->year),
                    ])
                    ->query(function ($query, array $data) {
                        $y = (int)($data['year'] ?? now()->year);
                        $m = (int)($data['month'] ?? 0);
                        if ($m >= 1 && $m <= 12) {
                            $start = now()->setDate($y, $m, 1)->startOfMonth();
                            $end   = now()->setDate($y, $m, 1)->endOfMonth();
                            $query->whereBetween('etd', [$start, $end]);
                        }
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('Ubah'),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()->label('Hapus'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'    => ShippingScheduleResource\Pages\ListShippingSchedules::route('/'),
            'overview' => ShippingScheduleResource\Pages\OverviewShippingSchedules::route('/overview'),
            'create'   => ShippingScheduleResource\Pages\CreateShippingSchedule::route('/create'),
            'edit'     => ShippingScheduleResource\Pages\EditShippingSchedule::route('/{record}/edit'),
        ];
    }
}
