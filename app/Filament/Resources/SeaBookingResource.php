<?php

namespace App\Filament\Resources;

use App\Enums\SeaBookingStatus;
use App\Filament\Resources\SeaBookingResource\Pages;
use App\Filament\Resources\SeaBookingResource\RelationManagers\ContainersRelationManager;
use App\Models\Depot;
use App\Models\SeaBooking;
use App\Models\Voyage;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;

class SeaBookingResource extends Resource
{
    protected static ?string $model = SeaBooking::class;
    protected static ?string $navigationGroup = 'Booking Kontainer (Laut)';
    protected static ?string $navigationIcon = 'heroicon-m-clipboard-document';
    protected static ?string $modelLabel = 'Sea Booking';

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            TextInput::make('code')
                ->label('Kode Booking')
                ->required()
                ->unique(ignoreRecord: true),
            Select::make('shipping_line_id')
                ->relationship('shippingLine', 'name')
                ->label('Shipping Line')
                ->searchable()
                ->preload()
                ->required()
                ->live(),

            Select::make('voyage_id')
                ->label('Voyage')
                ->searchable()
                ->preload()
                ->relationship(
                    name: 'voyage',
                    titleAttribute: 'voyage_no',
                    modifyQueryUsing: function ($query, Get $get) {
                        $sl = $get('shipping_line_id');
                        $query->with(['vessel', 'portFrom', 'portTo'])
                            ->when($sl, fn($q) => $q->where('shipping_line_id', $sl))
                            ->orderByDesc('etd');
                    }
                )
                ->getOptionLabelFromRecordUsing(function (Voyage $v) {
                    $vessel = $v->vessel?->name ?: '-';
                    $voy    = $v->voyage_no ?: '-';
                    $etd    = optional($v->etd)->format('d M Y') ?? '-';
                    $pol    = $v->portFrom?->code ?: ($v->portFrom?->name ?: '-');
                    $pod    = $v->portTo?->code   ?: ($v->portTo?->name   ?: '-');
                    return sprintf('%s / %s — %s (%s → %s)', $vessel, $voy, $etd, $pol, $pod);
                })
                ->rule(function (Get $get) {
                    return function (string $attribute, $value, $fail) use ($get) {
                        if (! $value) return;
                        $voy = Voyage::select('shipping_line_id')->find($value);
                        if ($voy && $get('shipping_line_id') && $voy->shipping_line_id !== (int) $get('shipping_line_id')) {
                            $fail('Voyage tidak sesuai dengan Shipping Line yang dipilih.');
                        }
                    };
                }),
            Select::make('depot_id')
                ->label('Depot')
                ->options(fn() => Depot::query()->orderBy('name')->pluck('name', 'id'))
                ->searchable()
                ->preload(),
            TextInput::make('ro_no')
                ->label('RO No'),
            TextInput::make('rc_no')
                ->label('RC No'),
            TextInput::make('si_no')
                ->label('SI No'),
            Select::make('status')
                ->label('Status')
                ->options(collect(SeaBookingStatus::cases())
                    ->mapWithKeys(fn($c) => [$c->value => $c
                        ->label()]))->required(),
            Textarea::make('notes')->label('Catatan')->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table->columns([
            TextColumn::make('code')->badge()->label('Kode'),
            TextColumn::make('shippingLine.name')->label('Line'),
            TextColumn::make('voyage.vessel.name')->label('Vessel'),
            TextColumn::make('voyage.voyage_no')->label('Voyage'),
            TextColumn::make('voyage.portFrom.code')->label('POL')->badge(),
            TextColumn::make('voyage.portTo.code')->label('POD')->badge(),
            TextColumn::make('status')->label('Status')->badge()
                ->state(fn($record) => $record->status?->label() ?? (string) $record->status)
                ->color(fn($record) => $record->status?->color() ?? 'gray'),
            TextColumn::make('updated_at')->since()->label('Diubah'),
        ])->filters([
            SelectFilter::make('status')->options(collect(SeaBookingStatus::cases())->mapWithKeys(fn($c) => [$c->value => $c->label()])),
            SelectFilter::make('shipping_line_id')->label('Shipping Line')->relationship('shippingLine', 'name'),
        ])->actions([
            Tables\Actions\EditAction::make()->label('Ubah'),
        ])->bulkActions([
            Tables\Actions\DeleteBulkAction::make()->label('Hapus'),
        ]);
    }

    public static function getRelations(): array
    {
        return [
            ContainersRelationManager::class,
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->with(['shippingLine', 'voyage.vessel', 'voyage.portFrom', 'voyage.portTo', 'depot']);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListSeaBookings::route('/'),
            'create' => Pages\CreateSeaBooking::route('/create'),
            'edit'   => Pages\EditSeaBooking::route('/{record}/edit'),
        ];
    }
}
