<?php

namespace App\Filament\FC\Resources;

use App\Filament\FC\Resources\ContainerReadinessSessionResource\Pages;
use App\Models\ContainerReadinessSession;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Container Readiness Session Resource.
 *
 * Disembunyikan dari navigasi — diakses via tombol di Monitoring Operasional.
 * Form: Tanggal, Jumlah Unit, Kebutuhan Container, Container Tersedia, Catatan.
 * gap & summary_sufficient dihitung otomatis via Model::booted().
 */
class ContainerReadinessSessionResource extends Resource
{
    protected static ?string $model = ContainerReadinessSession::class;

    protected static ?string $navigationGroup = 'Operasional Lapangan';
    protected static ?string $navigationLabel = 'Container Readiness';
    protected static ?string $modelLabel      = 'Data Container';
    protected static ?string $pluralLabel     = 'Data Container';
    protected static ?string $navigationIcon  = 'heroicon-o-archive-box';
    protected static ?int    $navigationSort  = 6;

    /** Disembunyikan dari nav — akses via Monitoring Operasional */
    protected static bool $shouldRegisterNavigation = false;

    public static function canViewAny(): bool
    {
        return Filament::auth()->user()?->isFieldCoordinator() ?? false;
    }

    // ── Form ─────────────────────────────────────────────────────────────────

    public static function form(Form $form): Form
    {
        return $form->schema([

            DatePicker::make('session_date')
                ->label('Tanggal')
                ->required()
                ->default(today())
                ->unique(
                    table: 'container_readiness_sessions',
                    column: 'session_date',
                    ignoreRecord: true,
                )
                ->validationMessages([
                    'unique' => 'Sudah ada data container untuk tanggal ini. Gunakan Edit.',
                ])
                ->columnSpanFull(),

            TextInput::make('unit_count')
                ->label('Jumlah Unit')
                ->helperText('Jumlah unit kendaraan / dokumen SPPB hari ini')
                ->numeric()
                ->minValue(0)
                ->default(0)
                ->required(),

            TextInput::make('container_need')
                ->label('Kebutuhan Container')
                ->helperText('Jumlah container yang dibutuhkan')
                ->numeric()
                ->minValue(0)
                ->default(0)
                ->required(),

            TextInput::make('container_available')
                ->label('Container Tersedia')
                ->helperText('Jumlah container yang tersedia / dikonfirmasi')
                ->numeric()
                ->minValue(0)
                ->default(0)
                ->required(),

            Textarea::make('notes')
                ->label('Catatan')
                ->rows(3)
                ->columnSpanFull(),

            Repeater::make('container_numbers')
                ->label('Nomor Container Tersedia')
                ->helperText('Masukkan satu nomor container per baris. Digunakan FC saat Planning Loading.')
                ->addActionLabel('Tambah Container')
                ->reorderable(false)
                ->columnSpanFull()
                ->schema([
                    TextInput::make('number')
                        ->label('Nomor Container')
                        ->placeholder('TGHU1234567')
                        ->maxLength(20)
                        ->required()
                        ->extraAttributes(['style' => 'font-family: monospace; text-transform: uppercase;']),
                ])
                ->dehydrateStateUsing(fn(array $state): array =>
                    collect($state)
                        ->map(fn($row) => strtoupper(trim($row['number'] ?? '')))
                        ->filter()
                        ->unique()
                        ->values()
                        ->all()
                )
                ->afterStateHydrated(function (Repeater $component, ?array $state): void {
                    if (empty($state)) {
                        $component->state([]);
                        return;
                    }
                    $component->state(
                        collect($state)
                            ->map(fn($item) => is_string($item) ? ['number' => $item] : $item)
                            ->values()
                            ->all()
                    );
                }),

        ])->columns(2);
    }

    // ── Table ─────────────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('session_date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('unit_count')
                    ->label('Unit')
                    ->alignCenter(),

                TextColumn::make('container_need')
                    ->label('Need')
                    ->alignCenter(),

                TextColumn::make('container_available')
                    ->label('Available')
                    ->alignCenter(),

                TextColumn::make('gap')
                    ->label('Gap')
                    ->alignCenter()
                    ->getStateUsing(fn ($record) => $record->gap >= 0 ? "+{$record->gap}" : (string) $record->gap)
                    ->color(fn ($record) => $record->summary_sufficient ? 'success' : 'danger'),

                IconColumn::make('summary_sufficient')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
            ])
            ->defaultSort('session_date', 'desc');
    }

    // ── Pages ─────────────────────────────────────────────────────────────────

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListContainerReadinessSessions::route('/'),
            'create' => Pages\CreateContainerReadinessSession::route('/create'),
            'edit'   => Pages\EditContainerReadinessSession::route('/{record}/edit'),
        ];
    }
}
