<?php

namespace App\Filament\Resources;

use App\Enums\MaintenanceReason;
use App\Enums\MaintenanceStatus;
use App\Filament\Resources\ArmadaMaintenanceResource\Pages;
use App\Models\Armada;
use App\Models\ArmadaMaintenance;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ArmadaMaintenanceResource extends Resource
{
    protected static ?string $model = ArmadaMaintenance::class;

    protected static ?string $navigationGroup  = 'Manajemen Armada';
    protected static ?string $navigationIcon   = 'heroicon-m-wrench';
    protected static ?string $navigationLabel  = 'Perawatan Armada';
    protected static ?string $pluralModelLabel = 'Perawatan Armada';
    protected static ?string $modelLabel       = 'Perawatan Armada';
    protected static ?int $navigationSort      = 20;

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Informasi Utama')
                ->schema([
                    Select::make('armada_id')
                        ->label('Armada')
                        ->relationship('armada', 'id')
                        ->getOptionLabelFromRecordUsing(function (?Armada $record): string {
                            if (! $record) return '—';
                            return $record->display_name ?: ('Armada #' . $record->id);
                        })
                        ->getOptionLabelUsing(function ($value): string {
                            $reason = $value ? Armada::find($value) : null;
                            return $reason?->display_name ?? ('Armada #' . (string) $value);
                        })
                        ->getSearchResultsUsing(function (string $search): array {
                            return Armada::query()
                                ->where('code', 'ILIKE', "%{$search}%")
                                ->orWhere('plate_number', 'ILIKE', "%{$search}%")
                                ->orderBy('code')->orderBy('plate_number')
                                ->limit(50)
                                ->get(['id'])
                                ->mapWithKeys(fn($reason) => [$reason->id => $reason->display_name ?? ('Armada #' . $reason->id)])
                                ->toArray();
                        })
                        ->searchable()
                        ->preload()
                        ->required()
                        ->columnSpan(2),

                    Select::make('status')
                        ->label('Status')
                        ->options(collect(MaintenanceStatus::cases())->mapWithKeys(fn($c) => [$c->value => $c->label()])->toArray())
                        ->default(MaintenanceStatus::InProgress->value)
                        ->native(false)
                        ->required()
                        ->reactive()
                        ->afterStateUpdated(function (callable $set, $state) {
                            if ($state === MaintenanceStatus::Scheduled->value) {
                                $set('started_at', null);
                                $set('closed_at', null);
                            } elseif ($state === MaintenanceStatus::InProgress->value) {
                                $set('closed_at', null);
                            } elseif ($state === MaintenanceStatus::Closed->value) {
                                $set('started_at', fn($get) => $get('started_at') ?: now());
                                $set('closed_at', fn($get) => $get('closed_at') ?: now());
                            }
                        }),
                    Select::make('reason_code')
                        ->label('Kategori')
                        ->options(
                            collect(MaintenanceReason::cases())
                                ->mapWithKeys(fn($c) => [$c->value => $c->label()])
                                ->toArray()
                        )
                        ->native(false)
                        ->searchable()
                        ->preload()
                        ->required(),

                    TextInput::make('odometer')
                        ->label('Odometer (km)')
                        ->numeric()
                        ->minValue(0),
                ])
                ->columns(4),

            Section::make('Waktu')
                ->schema([
                    DateTimePicker::make('started_at')
                        ->label('Mulai')
                        ->seconds(false)
                        ->native(false)
                        ->nullable()
                        ->dehydrated(fn(Get $get) => $get('status') !== MaintenanceStatus::Scheduled->value)
                        ->disabled(fn(Get $get) => $get('status') === MaintenanceStatus::Scheduled->value),

                    DateTimePicker::make('closed_at')
                        ->label('Selesai')
                        ->seconds(false)
                        ->native(false)
                        ->nullable()
                        ->helperText('Biarkan kosong jika perawatan masih berjalan.')
                        ->rule('after_or_equal:started_at')
                        ->dehydrated(fn(Get $get) => $get('status') === MaintenanceStatus::Closed->value)
                        ->disabled(fn(Get $get) => $get('status') !== MaintenanceStatus::Closed->value),
                ])
                ->columns(2),

            Section::make('Detail')
                ->schema([
                    Textarea::make('reason')
                        ->label('Alasan/Detail')
                        ->rows(3)
                        ->nullable()
                        ->helperText('Wajib diisi bila memilih kategori "Lainnya".'),

                    Textarea::make('note')
                        ->label('Catatan')
                        ->rows(3)
                        ->nullable(),
                ])
                ->columns(1),

            Placeholder::make('status_info')
                ->label('Status Saat Ini')
                ->content(fn(?ArmadaMaintenance $record) => $record?->status?->label() ?? '-')
                ->visible(fn(?ArmadaMaintenance $record) => filled($record)),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordUrl(fn($record) => static::getUrl('edit', ['record' => $record]))
            ->columns([
                TextColumn::make('armada.display_name')
                    ->label('Armada')
                    ->searchable(query: function ($query, string $search) {
                        $query->whereHas('armada', function ($query) use ($search) {
                            $query->where('code', 'ILIKE', "%{$search}%")
                                ->orWhere('plate_number', 'ILIKE', "%{$search}%");
                        });
                    })
                    ->sortable(query: function ($query, string $direction) {
                        $query->join('armadas', 'armadas.id', '=', 'armada_maintenances.armada_id')
                            ->orderBy('armadas.code', $direction)
                            ->orderBy('armadas.plate_number', $direction)
                            ->select('armada_maintenances.*');
                    }),

                TextColumn::make('status')
                    ->label('Status')
                    ->getStateUsing(fn($record) => $record->getRawOriginal('status'))
                    ->formatStateUsing(fn(string $state) => MaintenanceStatus::from($state)->label())
                    ->badge()
                    ->color(function (string $state) {
                        return match ($state) {
                            'scheduled'   => 'warning',
                            'in_progress' => 'info',
                            'closed'      => 'success',
                            default       => 'gray',
                        };
                    })
                    ->icon(function (string $state) {
                        return match ($state) {
                            'scheduled'   => 'heroicon-m-calendar',
                            'in_progress' => 'heroicon-m-clock',
                            'closed'      => 'heroicon-m-check-circle',
                            default       => null,
                        };
                    })
                    ->sortable(),

                TextColumn::make('reason_code')
                    ->label('Kategori')
                    ->getStateUsing(fn($record) => $record->getRawOriginal('reason_code'))
                    ->formatStateUsing(fn(?string $state) => $state ? MaintenanceReason::from($state)->label() : '-')
                    ->badge()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('reason')
                    ->label('Alasan/Detail')
                    ->limit(60)
                    ->wrap()
                    ->toggleable(),

                TextColumn::make('odometer')
                    ->label('Odometer')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('started_at')
                    ->label('Mulai')
                    ->dateTime('d M Y H:i')
                    ->sortable(),

                TextColumn::make('closed_at')
                    ->label('Selesai')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Diubah')
                    ->since()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options(
                        collect(MaintenanceStatus::cases())
                            ->mapWithKeys(fn($c) => [$c->value => $c->label()])
                            ->toArray()
                    ),

                Tables\Filters\SelectFilter::make('reason_code')
                    ->label('Kategori')
                    ->options(
                        collect(MaintenanceReason::cases())
                            ->mapWithKeys(fn($c) => [$c->value => $c->label()])
                            ->toArray()
                    )
                    ->searchable(),

                Tables\Filters\Filter::make('rentang_tanggal')
                    ->label('Rentang Mulai')
                    ->form([
                        DatePicker::make('from')->label('Dari'),
                        DatePicker::make('until')->label('Sampai'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'] ?? null, fn($query, $d) => $query->whereDate('started_at', '>=', $d))
                            ->when($data['until'] ?? null, fn($query, $d) => $query->whereDate('started_at', '<=', $d));
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('Ubah'),

                Tables\Actions\Action::make('close')
                    ->label('Tutup Tiket')
                    ->icon('heroicon-m-check-circle')
                    ->visible(fn(ArmadaMaintenance $row) => $row->status !== MaintenanceStatus::Closed)
                    ->requiresConfirmation()
                    ->action(function (ArmadaMaintenance $row) {
                        $row->update([
                            'status'     => MaintenanceStatus::Closed,
                            'started_at' => $row->started_at ?: now(),
                            'closed_at'  => now(),
                        ]);
                    }),

                Tables\Actions\DeleteAction::make()->label('Hapus'),
            ])
            ->defaultSort('started_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListArmadaMaintenances::route('/'),
            'create' => Pages\CreateArmadaMaintenance::route('/create'),
            'edit'   => Pages\EditArmadaMaintenance::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['armada.code', 'armada.plate_number', 'reason', 'reason_code', 'note'];
    }
}
