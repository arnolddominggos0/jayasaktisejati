<?php

namespace App\Filament\Resources\UnitResource\RelationManagers;

use App\Models\UnitInspection;
use App\Models\UnitInspectionItem;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * UnitInspectionsRelationManager
 *
 * Read-only inspection timeline untuk Unit (Admin panel — Quality Assurance).
 * Form schema juga didefinisikan di sini sebagai referensi untuk panel FC.
 *
 * Layout:
 *   Tabel : stage | gate_decision | status | source | checked_by | checked_at | ng_count
 *   Klik  : modal infolist berisi detail item per category (termasuk finding_type & foto)
 */
class UnitInspectionsRelationManager extends RelationManager
{
    protected static string $relationship          = 'inspections';
    protected static ?string $title               = 'Inspection Timeline';
    protected static ?string $recordTitleAttribute = 'stage';

    // ── Table ─────────────────────────────────────────────────────────────────

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('stage')
                    ->label('Stage')
                    ->formatStateUsing(fn (string $state) => UnitInspection::STAGE_LABELS[$state] ?? ucfirst($state))
                    ->icon(fn (string $state) => match ($state) {
                        'pickup'          => 'heroicon-m-truck',
                        'handover_depot'  => 'heroicon-m-building-storefront',
                        'loading'         => 'heroicon-m-arrow-up-tray',
                        'unloading'       => 'heroicon-m-arrow-down-tray',
                        'selfdrive'       => 'heroicon-m-map-pin',
                        'dooring'         => 'heroicon-m-home-modern',
                        default           => 'heroicon-m-check-circle',
                    })
                    ->weight('bold'),

                TextColumn::make('gate_decision')
                    ->label('Gate Decision')
                    ->badge()
                    ->color(fn (?string $state) => match ($state) {
                        UnitInspection::GATE_ACCEPT            => 'success',
                        UnitInspection::GATE_ALLOW_WITH_REMARK => 'warning',
                        UnitInspection::GATE_RETURN_TO_PDC     => 'danger',
                        default                                 => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state) => match ($state) {
                        UnitInspection::GATE_ACCEPT            => 'ACCEPT',
                        UnitInspection::GATE_ALLOW_WITH_REMARK => 'ALLOW W/ REMARK',
                        UnitInspection::GATE_RETURN_TO_PDC     => 'RETURN TO PDC',
                        default                                 => '— Pending',
                    }),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state) => $state === 'passed' ? 'success' : 'danger')
                    ->formatStateUsing(fn (string $state) => strtoupper($state)),

                TextColumn::make('source')
                    ->label('Sumber')
                    ->badge()
                    ->color(fn (string $state) => $state === 'live' ? 'info' : 'gray')
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'live'              => 'Live',
                        'historical_import' => 'Historis',
                        default             => $state,
                    }),

                TextColumn::make('checkedBy.name')
                    ->label('Pemeriksa')
                    ->default('—')
                    ->toggleable(),

                TextColumn::make('items_count')
                    ->label('Items')
                    ->counts('items')
                    ->badge()
                    ->color('primary'),

                TextColumn::make('ng_count')
                    ->label('NG')
                    ->getStateUsing(fn (UnitInspection $record) => $record->items()->where('result', 'ng')->count())
                    ->badge()
                    ->color(fn (int $state) => $state > 0 ? 'danger' : 'gray'),

                TextColumn::make('checked_at')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),
            ])
            ->actions([
                ViewAction::make()
                    ->label('Detail')
                    ->infolist(fn (Infolist $infolist) => $this->buildItemInfolists($infolist)),
            ])
            ->paginated(false)
            ->striped();
    }

    // ── Infolist modal — detail items ─────────────────────────────────────────

    private function buildItemInfolists(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Section::make(fn (UnitInspection $record) => UnitInspection::STAGE_LABELS[$record->stage] ?? $record->stage)
                ->description(fn (UnitInspection $record) => implode('  ·  ', array_filter([
                    'Gate: ' . (UnitInspection::GATE_LABELS[$record->gate_decision] ?? '—'),
                    'Status: ' . strtoupper($record->status),
                    'Sumber: ' . ($record->source === 'historical_import' ? 'Historis' : 'Live'),
                    $record->checkedBy ? 'Pemeriksa: ' . $record->checkedBy->name : null,
                    $record->checked_at ? 'Tanggal: ' . $record->checked_at->format('d M Y') : null,
                ])))
                ->schema([
                    RepeatableEntry::make('items')
                        ->label('')
                        ->schema([
                            Grid::make(4)
                                ->schema([
                                    TextEntry::make('category')
                                        ->label('Kategori')
                                        ->badge()
                                        ->color('gray'),

                                    TextEntry::make('item_name')
                                        ->label('Item')
                                        ->weight('medium'),

                                    TextEntry::make('result')
                                        ->label('Hasil')
                                        ->badge()
                                        ->color(fn (string $state) => $state === 'ok' ? 'success' : 'danger')
                                        ->formatStateUsing(fn (string $state) => strtoupper($state)),

                                    TextEntry::make('finding_type')
                                        ->label('Jenis Temuan')
                                        ->badge()
                                        ->color(fn (?string $state) => match ($state) {
                                            UnitInspectionItem::FINDING_MAJOR_DAMAGE     => 'danger',
                                            UnitInspectionItem::FINDING_MINOR_MISSING    => 'warning',
                                            UnitInspectionItem::FINDING_INFORMATION_ONLY => 'gray',
                                            default                                       => 'gray',
                                        })
                                        ->formatStateUsing(fn (?string $state) => UnitInspectionItem::FINDING_LABELS[$state] ?? '—')
                                        ->visible(fn (UnitInspectionItem $record) => $record->result === UnitInspectionItem::RESULT_NG),
                                ]),

                            TextEntry::make('notes')
                                ->label('Catatan')
                                ->default('—')
                                ->columnSpanFull()
                                ->visible(fn (UnitInspectionItem $record) => filled($record->notes)),

                            ImageEntry::make('photo_url')
                                ->label('Foto Bukti')
                                ->disk('public')
                                ->columnSpanFull()
                                ->visible(fn (UnitInspectionItem $record) => filled($record->photo_url)),
                        ]),
                ]),
        ]);
    }

    // ── Form (schema referensi untuk panel FC) ────────────────────────────────
    // canCreate() = false — form ini tidak aktif di admin panel.
    // Didefinisikan sebagai referensi dan akan digunakan oleh panel FC.

    public function form(Form $form): Form
    {
        return $form->schema([
            Select::make('stage')
                ->label('Stage')
                ->options(UnitInspection::STAGE_LABELS)
                ->required()
                ->disabled(fn (?UnitInspection $record) => $record !== null),

            Hidden::make('checked_by')
                ->default(fn () => auth()->id()),

            Hidden::make('source')
                ->default(UnitInspection::SOURCE_LIVE),

            DateTimePicker::make('checked_at')
                ->label('Waktu Inspeksi')
                ->seconds(false)
                ->default(now())
                ->required(),

            Textarea::make('notes')
                ->label('Catatan Umum')
                ->rows(2),

            Repeater::make('items')
                ->label('Item Pemeriksaan')
                ->relationship('items')
                ->schema([
                    TextInput::make('category')
                        ->label('Kategori')
                        ->disabled(),

                    TextInput::make('item_name')
                        ->label('Item')
                        ->disabled(),

                    ToggleButtons::make('result')
                        ->label('Hasil')
                        ->options([
                            UnitInspectionItem::RESULT_OK => 'OK',
                            UnitInspectionItem::RESULT_NG => 'NG',
                        ])
                        ->colors([
                            UnitInspectionItem::RESULT_OK => 'success',
                            UnitInspectionItem::RESULT_NG => 'danger',
                        ])
                        ->default(UnitInspectionItem::RESULT_OK)
                        ->required()
                        ->live()
                        ->grouped(),

                    Select::make('finding_type')
                        ->label('Jenis Temuan')
                        ->options(UnitInspectionItem::FINDING_LABELS)
                        ->required(fn (Get $get) => $get('result') === UnitInspectionItem::RESULT_NG)
                        ->hidden(fn (Get $get) => $get('result') !== UnitInspectionItem::RESULT_NG)
                        ->live(),

                    Textarea::make('notes')
                        ->label('Catatan / Deskripsi Temuan')
                        ->rows(2)
                        ->required(fn (Get $get) => $get('result') === UnitInspectionItem::RESULT_NG)
                        ->hidden(fn (Get $get) => $get('result') !== UnitInspectionItem::RESULT_NG),

                    FileUpload::make('photo_url')
                        ->label('Foto Bukti')
                        ->image()
                        ->disk('public')
                        ->directory('inspection-photos')
                        ->maxSize(5120)
                        ->required(fn (Get $get) => $get('finding_type') === UnitInspectionItem::FINDING_MAJOR_DAMAGE)
                        ->hidden(fn (Get $get) => $get('finding_type') !== UnitInspectionItem::FINDING_MAJOR_DAMAGE),
                ])
                ->addable(false)
                ->deletable(false)
                ->reorderable(false)
                ->columnSpanFull(),
        ]);
    }

    public function canCreate(): bool
    {
        return false;
    }
}
