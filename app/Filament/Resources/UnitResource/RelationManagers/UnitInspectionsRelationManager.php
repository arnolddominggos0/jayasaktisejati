<?php

namespace App\Filament\Resources\UnitResource\RelationManagers;

use App\Models\UnitInspection;
use App\Models\UnitInspectionItem;
use Filament\Forms\Form;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * UnitInspectionsRelationManager
 *
 * Read-only timeline inspection untuk Unit.
 * Ditampilkan di UnitResource → View page sebagai tab "Inspection Timeline".
 *
 * Layout:
 *   Tabel : stage | status | source | checked_at | jumlah_items | ng_count
 *   Klik  : modal infolist berisi detail item per category
 */
class UnitInspectionsRelationManager extends RelationManager
{
    protected static string $relationship      = 'inspections';
    protected static ?string $title            = 'Inspection Timeline';
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
                        'live'               => 'Live',
                        'historical_import'  => 'Historis',
                        default              => $state,
                    }),

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
                ->description(fn (UnitInspection $record) => sprintf(
                    'Status: %s | Sumber: %s | Tanggal: %s',
                    strtoupper($record->status),
                    $record->source === 'historical_import' ? 'Historis' : 'Live',
                    $record->checked_at?->format('d M Y') ?? '—'
                ))
                ->schema([
                    RepeatableEntry::make('items')
                        ->label('')
                        ->schema([
                            Grid::make(3)
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
                                ]),

                            TextEntry::make('notes')
                                ->label('Catatan')
                                ->default('—')
                                ->columnSpanFull()
                                ->visible(fn (UnitInspectionItem $record) => filled($record->notes)),
                        ]),
                ]),
        ]);
    }

    // ── No create / edit / delete ─────────────────────────────────────────────

    public function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public function canCreate(): bool
    {
        return false;
    }
}
