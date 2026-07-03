<?php

namespace App\Filament\Resources\VesselPlanResource\RelationManagers;

use App\Models\VesselPlan;
use Filament\Forms;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextColumn\TextColumnSize;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class VesselPlanItemRelationManager extends RelationManager
{
    protected static string $relationship = 'items';
    protected static ?string $title = 'Draft Jadwal Kapal';

    /**
     * Sprint 4.x — Vessel Plan Workflow Language Alignment.
     * Judul & helper section mengikuti fase bisnis (bukan status teknis semata),
     * supaya Planner langsung sadar konteks tanpa berpindah halaman.
     */
    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return self::sectionCopy($ownerRecord)['title'];
    }

    protected function getTableHeading(): string
    {
        return self::sectionCopy($this->getOwnerRecord())['title'];
    }

    /**
     * @return array{title: string, description: string}
     */
    protected static function sectionCopy(?Model $plan): array
    {
        if (! $plan instanceof VesselPlan) {
            return [
                'title' => 'Draft Jadwal Kapal',
                'description' => 'Menyusun jadwal kapal berdasarkan informasi dari Shipping Line.',
            ];
        }

        return match (true) {
            $plan->isFinal() => [
                'title' => 'Jadwal Final',
                'description' => 'Jadwal telah difinalisasi dan menjadi dasar pembentukan Voyage.',
            ],
            $plan->isSent(), $plan->isRevision() => [
                'title' => 'Final Schedule TAM',
                'description' => 'Memperbarui data sesuai Final Schedule yang diterima dari TAM.',
            ],
            default => [
                'title' => 'Draft Jadwal Kapal',
                'description' => 'Menyusun jadwal kapal berdasarkan informasi dari Shipping Line.',
            ],
        };
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Informasi Kapal')
                ->schema([
                    Select::make('shipping_line_id')
                        ->label('Pelayaran')
                        ->relationship('shippingLine', 'name')
                        ->required()
                        ->live()
                        ->afterStateUpdated(fn($set) => $set('vessel_id', null)),

                    Select::make('vessel_id')
                        ->label('Kapal')
                        ->relationship(
                            'vessel',
                            'name',
                            fn($query, Get $get) =>
                            $query->where('shipping_line_id', $get('shipping_line_id'))
                        )
                        ->required()
                        ->disabled(fn(Get $get) => blank($get('shipping_line_id'))),
                ])
                ->columns(2),

            Forms\Components\Section::make('Jadwal')
                ->schema([
                    DateTimePicker::make('planned_etb')
                        ->label('ETB (Rencana Sandar)')
                        ->native(false)
                        ->helperText('Opsional. Waktu estimasi kapal sandar.'),

                    DateTimePicker::make('planned_etd')
                        ->label('ETD (Rencana)')
                        ->required()
                        ->native(false),

                    DateTimePicker::make('planned_eta')
                        ->label('ETA (Rencana)')
                        ->required()
                        ->native(false),
                ])
                ->columns(2),

            Forms\Components\Section::make('Informasi Voyage')
                ->schema([
                    TextInput::make('voyage_no')
                        ->label('No Voyage')
                        ->maxLength(50)
                        ->helperText('Nomor voyage dari shipping line.'),
                ])
                ->columns(1),

            Forms\Components\Section::make('Final Schedule TAM')
                ->description('Dicatat setelah menerima Final Schedule dari TAM.')
                ->visible(fn() => ! ($this->getOwnerRecord()?->isDraft() ?? true))
                ->schema([
                    TextInput::make('cargo_plan')
                        ->label('Cargo Plan (unit)')
                        ->numeric()
                        ->minValue(0)
                        ->helperText('Alokasi unit sesuai Final Schedule dari TAM.'),
                ])
                ->columns(1),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->description(self::sectionCopy($this->getOwnerRecord())['description'])
            ->columns([
                TextColumn::make('vessel.name')
                    ->label('Kapal / Voyage')
                    ->weight('semibold')
                    ->description(function ($record) {
                        $parts = array_filter([
                            $record->voyage_no ? 'V.' . $record->voyage_no : null,
                            $record->shippingLine?->name,
                        ]);

                        return implode(' · ', $parts) ?: null;
                    }),

                TextColumn::make('planned_etb')
                    ->label('ETB')
                    ->formatStateUsing(fn($state) => $state?->translatedFormat('d M Y'))
                    ->placeholder('—'),

                TextColumn::make('planned_etd')
                    ->label('ETD')
                    ->formatStateUsing(fn($state) => $state?->translatedFormat('d M Y'))
                    ->placeholder('—'),

                TextColumn::make('planned_eta')
                    ->label('ETA')
                    ->formatStateUsing(fn($state) => $state?->translatedFormat('d M Y'))
                    ->placeholder('—'),

                TextColumn::make('cargo_plan')
                    ->label('Cargo Plan')
                    ->alignEnd()
                    ->placeholder('—')
                    ->visible(fn() => ! ($this->getOwnerRecord()?->isDraft() ?? true)),

                TextColumn::make('planned_sailing')
                    ->label('Sailing')
                    ->alignEnd()
                    ->getStateUsing(function ($record) {
                        if (!$record->planned_etd || !$record->planned_eta) {
                            return '—';
                        }

                        return $record->planned_etd->diffInDays($record->planned_eta) . ' hari';
                    }),

                TextColumn::make('etd_gap')
                    ->label('ETD Gap')
                    ->alignCenter()
                    ->badge()
                    ->size(TextColumnSize::ExtraSmall)
                    ->getStateUsing(function ($record) {
                        $plan = $this->getOwnerRecord();
                        if (! $plan) return '—';

                        $gap = $plan->etdGaps()[$record->id] ?? null;
                        return $gap === null ? '—' : "{$gap} hari";
                    })
                    ->color(function ($record) {
                        $plan = $this->getOwnerRecord();
                        $gap = $plan?->etdGaps()[$record->id] ?? null;

                        return match (true) {
                            $gap === null => 'gray',
                            $gap > 10     => 'danger',
                            $gap > 6      => 'warning',
                            default       => 'success',
                        };
                    }),
            ])

            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Tambah Jadwal')
                    ->visible(fn() => $this->getOwnerRecord()?->isEditable()),
            ])

            ->actions([
                Tables\Actions\EditAction::make()
                    ->iconButton()
                    ->tooltip('Ubah')
                    ->visible(fn() => $this->getOwnerRecord()?->isEditable()),

                Tables\Actions\DeleteAction::make()
                    ->iconButton()
                    ->tooltip('Hapus')
                    ->visible(fn() => $this->getOwnerRecord()?->isEditable()),
            ])

            ->defaultSort('planned_etd');
    }
}
