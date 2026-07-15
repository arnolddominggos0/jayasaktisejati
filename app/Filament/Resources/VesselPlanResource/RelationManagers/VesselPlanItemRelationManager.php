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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\On;

class VesselPlanItemRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Jadwal Kapal';

    // Filter Shipping Line dikendalikan dari toolbar di blade parent lewat
    // event Livewire, diterapkan ke query tabel via modifyQueryUsing() di bawah.
    public ?string $vpShippingLineFilter = null;

    #[On('vpFilterShippingLine')]
    public function applyVpShippingLineFilter(?string $value = null): void
    {
        $this->vpShippingLineFilter = filled($value) ? $value : null;

        // Reset pagination to avoid hidden rows when filter narrows result set.
        $this->resetPage();
    }

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        // Section title (identitas tab) berbeda dari table heading —
        // lihat getTableHeading().
        return self::sectionCopy($ownerRecord)['title'];
    }

    // Judul tabel dikosongkan karena Workspace Header di halaman parent
    // sudah menampilkan judul dan konteks yang sama; heading kosong membuat
    // baris header tabel native Filament otomatis menyisakan hanya action
    // "Tambah Jadwal" rata kanan.
    protected function getTableHeading(): string
    {
        return '';
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

        // Description dikosongkan di seluruh status — section header di
        // parent blade sudah jadi satu-satunya deskripsi workflow tab ini.
        return match (true) {
            $plan->isFinal() => ['title' => 'Jadwal Kapal', 'description' => null],
            $plan->isSent(), $plan->isRevision() => ['title' => 'Jadwal Kapal', 'description' => null],
            default => ['title' => 'Jadwal Kapal', 'description' => null],
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
                            fn($query, Get $get) => $query->where('shipping_line_id', $get('shipping_line_id'))
                        )
                        ->required()
                        ->disabled(fn(Get $get) => blank($get('shipping_line_id'))),
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

            Forms\Components\Section::make('Rencana Muatan')
                ->description('Dicatat setelah menerima Final Schedule dari TAM.')
                ->visible(fn() => ! ($this->getOwnerRecord()?->isDraft() ?? true))
                ->schema([
                    TextInput::make('cargo_plan')
                        ->label('Rencana Muatan (unit)')
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
            ->modifyQueryUsing(function (Builder $query) {
                return $query->when(
                    filled($this->vpShippingLineFilter),
                    fn(Builder $q) => $q->where('shipping_line_id', $this->vpShippingLineFilter)
                );
            })
            ->columns([
                // Kolom Kapal paling lebar — identifier utama baris.
                TextColumn::make('vessel.name')
                    ->label('Kapal / Voyage')
                    ->weight('semibold')
                    ->width('w-[28rem]')
                    ->description(function ($record) {
                        $parts = array_filter([
                            $record->voyage_no ? 'V.' . $record->voyage_no : null,
                            $record->shippingLine?->name,
                        ]);

                        return implode(' · ', $parts) ?: null;
                    }),

                TextColumn::make('planned_etb')
                    ->label('ETB')
                    ->alignCenter()
                    ->width('w-32')
                    ->formatStateUsing(fn($state) => $state?->translatedFormat('d M Y'))
                    ->placeholder('—'),

                TextColumn::make('planned_etd')
                    ->label('ETD')
                    ->alignCenter()
                    ->width('w-32')
                    ->formatStateUsing(fn($state) => $state?->translatedFormat('d M Y'))
                    ->placeholder('—'),

                TextColumn::make('planned_eta')
                    ->label('ETA')
                    ->alignCenter()
                    ->width('w-32')
                    ->formatStateUsing(fn($state) => $state?->translatedFormat('d M Y'))
                    ->placeholder('—'),

                // Kosong ditampilkan sebagai "Belum diisi" (abu), bukan dash —
                // field ini baru terisi setelah Final Schedule dari TAM diterima.
                TextColumn::make('cargo_plan')
                    ->label('Rencana Muatan')
                    ->alignCenter()
                    ->width('w-28')
                    ->placeholder('Belum diisi')
                    ->color(fn($state) => filled($state) ? null : 'gray')
                    ->visible(fn() => ! ($this->getOwnerRecord()?->isDraft() ?? true)),

                TextColumn::make('planned_sailing')
                    ->label('Sailing')
                    ->alignCenter()
                    ->width('w-28')
                    ->getStateUsing(function ($record) {
                        if (! $record->planned_etd || ! $record->planned_eta) {
                            return null;
                        }

                        return $record->planned_etd->diffInDays($record->planned_eta) . ' hari';
                    })
                    ->placeholder('Belum diisi')
                    ->color(fn($state) => filled($state) ? null : 'gray'),

                TextColumn::make('etd_gap')
                    ->label('ETD Gap')
                    ->alignCenter()
                    ->width('w-28')
                    ->badge()
                    ->size(TextColumnSize::ExtraSmall)
                    ->getStateUsing(function ($record) {
                        $plan = $this->getOwnerRecord();
                        if (! $plan) {
                            return '—';
                        }

                        $gap = $plan->etdGaps()[$record->id] ?? null;

                        return $gap === null ? '—' : "{$gap} hari";
                    })
                    ->color(function ($record) {
                        $plan = $this->getOwnerRecord();
                        $gap = $plan?->etdGaps()[$record->id] ?? null;

                        return match (true) {
                            $gap === null => 'gray',
                            $gap > 10 => 'danger',
                            $gap > 6 => 'warning',
                            default => 'success',
                        };
                    }),
            ])
            ->actionsAlignment('center')

            // Pesan empty state berbeda tergantung apakah filter Shipping
            // Line sedang aktif, supaya jelas apakah memang belum ada
            // jadwal sama sekali atau hanya tidak ada untuk filter ini.
            ->emptyStateHeading('Belum ada jadwal pada Vessel Plan ini')
            ->emptyStateDescription(function () {
                return filled($this->vpShippingLineFilter)
                    ? 'Belum ada jadwal untuk Shipping Line ini.'
                    : 'Tambahkan jadwal kapal pertama untuk mulai menyusun rencana pengiriman bulan ini.';
            })
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Tambah Jadwal')
                    ->icon('heroicon-o-plus')
                    ->visible(fn() => $this->getOwnerRecord()?->isEditable()),
            ])

            // Toolbar CTA disembunyikan saat tabel benar-benar kosong supaya
            // tidak dobel dengan CTA di dalam empty state (aksi yang sama,
            // area yang sama). Begitu ada 1 jadwal, empty state hilang dan
            // toolbar CTA ini jadi satu-satunya CTA "Tambah Jadwal" lagi.
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Tambah Jadwal')
                    ->icon('heroicon-o-plus')
                    ->visible(fn() => $this->getOwnerRecord()?->isEditable()
                        && $this->getOwnerRecord()->items->isNotEmpty()),
            ])

            ->actions([
                Tables\Actions\EditAction::make()
                    ->iconButton()
                    ->extraAttributes(['class' => 'mx-0.5'])
                    ->tooltip('Ubah')
                    ->visible(fn() => $this->getOwnerRecord()?->isEditable()),

                Tables\Actions\DeleteAction::make()
                    ->iconButton()
                    ->extraAttributes(['class' => 'mx-0.5'])
                    ->tooltip('Hapus')
                    ->visible(fn() => $this->getOwnerRecord()?->isEditable()),
            ])

            ->defaultSort('planned_etd');
    }
}
