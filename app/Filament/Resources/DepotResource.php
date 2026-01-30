<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DepotResource\Pages;
use App\Models\Depot;
use App\Models\Port;
use App\Models\Shipment;
use Closure;
use Filament\Forms;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;

class DepotResource extends Resource
{
    protected static ?string $model = Depot::class;

    protected static ?string $navigationGroup = 'Master Data';
    protected static ?string $navigationLabel = 'Depo';
    protected static ?string $pluralLabel     = 'Depo';
    protected static ?string $navigationIcon  = 'heroicon-m-building-office-2';
    protected static ?string $modelLabel      = 'Depo';
    protected static ?int    $navigationSort  = 40;

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            TextInput::make('code')
                ->label('Kode')
                ->required()
                ->maxLength(30)
                ->unique(ignoreRecord: true),

            TextInput::make('name')
                ->label('Nama')
                ->required()
                ->maxLength(120),

            Select::make('mode')
                ->label('Moda')
                ->options([
                    'sea'  => 'Laut',
                    'land' => 'Darat',
                ])
                ->required()
                ->native(false)
                ->live()
                ->formatStateUsing(fn($state) => $state === 'sea_freight' ? 'sea' : $state)
                ->dehydrateStateUsing(fn($state) => $state === 'sea_freight' ? 'sea' : $state)
                ->rules(['in:sea,land']),

            Select::make('port_id')
                ->label('Pelabuhan (khusus Laut)')
                ->relationship('port', 'name')
                ->getOptionLabelFromRecordUsing(fn(Port $record) => "{$record->code} — {$record->name}")
                ->searchable()
                ->preload()
                ->visible(fn(Get $get) => $get('mode') === 'sea')
                ->required(fn(Get $get) => $get('mode') === 'sea')
                ->createOptionForm([
                    TextInput::make('code')->label('Kode')->required()->maxLength(10),
                    TextInput::make('name')->label('Nama')->required()->maxLength(120),
                    TextInput::make('city')->label('Kota')->maxLength(120),
                    TextInput::make('country')->label('Negara')->maxLength(120),
                ])
                ->createOptionAction(fn(Action $action) => $action->label('Tambah Pelabuhan'))
                ->rules([
                    fn(Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
                        if ($get('mode') !== 'sea' || !$value) return;
                        $exists = Depot::query()
                            ->where('branch_id', $get('branch_id'))
                            ->where('mode', 'sea')
                            ->where('port_id', $value)
                            ->when($get('id') ?? null, fn($q, $id) => $q->where('id', '!=', $id))
                            ->exists();
                        if ($exists) $fail('Depo laut untuk cabang & pelabuhan ini sudah ada.');
                    },
                ]),

            CheckboxList::make('service_types')
                ->label('Jenis Layanan (khusus Darat)')
                ->options([
                    'land_trucking' => 'Trucking',
                    'car_carrier'   => 'Car Carrier',
                    'towing'        => 'Towing',
                ])
                ->visible(fn(Get $get) => $get('mode') === 'land')
                ->columns(2),

            Textarea::make('address')
                ->label('Alamat')
                ->rows(3)
                ->columnSpanFull(),

            Select::make('branch_id')
                ->relationship('branch', 'name')
                ->label('Cabang')
                ->required()
                ->searchable()
                ->preload()
                ->native(false),

            Select::make('coordinator_user_id')
                ->label('Koordinator Lapangan')
                ->options(\App\Models\User::role('field_coordinator')->orderBy('name')->pluck('name', 'id'))
                ->searchable()
                ->nullable()
                ->helperText('Satu koordinator hanya bisa di satu depo.')
                ->rules([
                    fn(Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
                        if (!$value) return;
                        $exists = Depot::query()
                            ->where('coordinator_user_id', $value)
                            ->when($get('id') ?? null, fn($q, $id) => $q->where('id', '!=', $id))
                            ->exists();
                        if ($exists) $fail('Koordinator ini sudah ditetapkan di depo lain.');
                    },
                ]),
        ])->columns(2);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table->columns([
            TextColumn::make('code')->badge()->label('Kode')->searchable(),
            TextColumn::make('name')->label('Nama')->searchable(),
            TextColumn::make('mode')->badge()->label('Moda')
                ->formatStateUsing(fn($state) => ($state === 'sea_freight' ? 'sea' : $state) === 'sea' ? 'Laut' : 'Darat'),
            TextColumn::make('port.code')->label('Kode Pelabuhan')->badge()->toggleable()->toggledHiddenByDefault(),
            TextColumn::make('port.name')->label('Pelabuhan')->default('-')->toggleable(),
            TextColumn::make('service_types')->label('Layanan')
                ->formatStateUsing(fn($state) => is_array($state) ? implode(', ', $state) : '-'),
            TextColumn::make('branch.name')->label('Cabang')->badge(),
            TextColumn::make('coordinator.name')->label('Koordinator'),
            TextColumn::make('updated_at')->since()->label('Diubah'),
        ])->actions([
            Tables\Actions\EditAction::make()->label('Ubah'),
            Tables\Actions\DeleteAction::make()
                ->label('Hapus')
                ->requiresConfirmation()
                ->before(function ($record, $action) {
                    if ($record->shipments()->exists()) {
                        $action->halt();
                        Notification::make()
                            ->title('Tidak bisa menghapus depo')
                            ->body('Depo masih dipakai oleh shipment. Pindahkan dulu shipment ke depo lain.')
                            ->danger()
                            ->send();
                    }
                })
                ->successNotificationTitle('Depo dihapus'),
        ])->bulkActions([
            Tables\Actions\BulkAction::make('safe_delete')
                ->label('Hapus Terpilih')
                ->icon('heroicon-m-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->action(function ($records) {
                    $blocked = [];
                    foreach ($records as $depot) {
                        $inUse = Shipment::where('assigned_depot_id', $depot->id)
                            ->where('mode', 'sea')
                            ->exists();
                        if ($inUse) {
                            $blocked[] = $depot->code ?: $depot->name ?: ('ID ' . $depot->id);
                            continue;
                        }
                        try { $depot->delete(); } catch (\Throwable $e) {
                            $blocked[] = $depot->code ?: $depot->name ?: ('ID ' . $depot->id);
                        }
                    }
                    if (! empty($blocked)) {
                        Notification::make()->title('Sebagian gagal dihapus')->body('Depo berikut masih dipakai: ' . implode(', ', $blocked))->danger()->send();
                    } else {
                        Notification::make()->title('Depo terpilih dihapus')->success()->send();
                    }
                }),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListDepots::route('/'),
            'create' => Pages\CreateDepot::route('/create'),
            'edit'   => Pages\EditDepot::route('/{record}/edit'),
        ];
    }
}
