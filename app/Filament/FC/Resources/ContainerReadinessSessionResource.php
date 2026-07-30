<?php

namespace App\Filament\FC\Resources;

use App\Enums\ContainerAllocationType;
use App\Filament\FC\Resources\ContainerReadinessSessionResource\Pages;
use App\Models\BriefingSession;
use App\Models\ContainerReadinessSession;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Support\Enums\Alignment;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;
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
                ->live(onBlur: true)
                ->columnSpanFull(),

            Placeholder::make('unit_count_display')
                ->label('Jumlah Unit')
                ->helperText('Otomatis — unit yang Handover pada tanggal ini.')
                ->content(function (Get $get, ?ContainerReadinessSession $record): string {
                    $date = $get('session_date');

                    // Baris legacy sebelum YARD_CUTOFF mempertahankan angka tersimpan.
                    if ($record && $date && (string) $date < BriefingSession::YARD_CUTOFF) {
                        return $record->unit_count . ' unit';
                    }

                    $probe = new ContainerReadinessSession(['session_date' => $date]);

                    return $probe->derived_unit_count . ' unit';
                }),

            TextInput::make('container_need')
                ->label('Kebutuhan Container')
                ->helperText('Keputusan FC — dipengaruhi jenis & kombinasi kendaraan, Regular vs Rack.')
                ->numeric()
                ->minValue(0)
                ->default(0)
                ->required()
                ->live(onBlur: true),

            Section::make('Container Readiness Summary')
                ->description('Dihitung otomatis dari Kebutuhan Container dan Daftar Container di bawah.')
                ->columns(3)
                ->columnSpanFull()
                ->schema([
                    Placeholder::make('container_available_display')
                        ->label('Container Tersedia')
                        ->content(fn (Get $get): string => self::listedContainerCount($get) . ' container'),

                    Placeholder::make('gap_display')
                        ->label('Gap')
                        ->content(function (Get $get): string {
                            $gap = self::listedContainerCount($get) - (int) $get('container_need');

                            return $gap >= 0 ? "+{$gap}" : (string) $gap;
                        }),

                    Placeholder::make('ready_display')
                        ->label('Status')
                        ->content(function (Get $get): HtmlString {
                            [$label, $color] = self::readinessBadge($get);

                            return new HtmlString(Blade::render(
                                '<x-filament::badge :color="$color" size="lg">{{ $label }}</x-filament::badge>',
                                ['color' => $color, 'label' => $label],
                            ));
                        }),
                ]),

            Section::make('Daftar Container')
                ->description('Satu-satunya sumber Container Tersedia. Setiap container wajib diisi nomor dan service (Regular/Rack). Digunakan FC saat Planning Stuffing.')
                ->columnSpanFull()
                ->schema([
            Repeater::make('container_numbers')
                ->hiddenLabel()
                ->addActionLabel('Tambah Container')
                ->addActionAlignment(Alignment::End)
                ->reorderable(false)
                ->live()
                ->columnSpanFull()
                ->schema([
                    TextInput::make('number')
                        ->label('Container Number')
                        ->placeholder('REG001')
                        ->maxLength(20)
                        ->required()
                        ->extraAttributes(['style' => 'font-family: monospace; text-transform: uppercase;']),
                    Select::make('service')
                        ->label('Container Service')
                        ->options([
                            ContainerAllocationType::Regular->value => ContainerAllocationType::Regular->label(),
                            ContainerAllocationType::Rack->value => ContainerAllocationType::Rack->label(),
                        ])
                        ->native(false)
                        ->required(),
                ])
                ->columns(2)
                ->dehydrateStateUsing(fn(array $state): array =>
                    collect($state)
                        ->map(fn($row) => [
                            'number' => strtoupper(trim($row['number'] ?? '')),
                            'service' => $row['service'] ?? null,
                        ])
                        ->filter(fn(array $row) => $row['number'] !== '')
                        ->unique('number')
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
                            // Baris lama (sebelum CR-02) tersimpan sebagai string polos
                            // tanpa service — dinormalisasi agar form tetap bisa dibuka,
                            // operator diminta melengkapi service saat menyunting.
                            ->map(fn($item) => is_string($item) ? ['number' => $item, 'service' => null] : $item)
                            ->values()
                            ->all()
                    );
                }),
                ]),

            Textarea::make('notes')
                ->label('Catatan')
                ->rows(3)
                ->columnSpanFull(),

        ])->columns(2);
    }

    /**
     * Jumlah container unik pada Daftar Container yang sedang diisi di form.
     * Dipakai preview Container Tersedia / Gap / Status.
     * Nilai final tetap dihitung ContainerReadinessSession::booted().
     */
    /**
     * Label + warna badge untuk Status pada form.
     *
     * PENYAJIAN UI SAJA — business rule tidak berubah. Model tetap menyimpan
     * summary_sufficient = (available >= need), termasuk untuk kondisi 0 >= 0.
     * Yang dibedakan di sini hanyalah kondisi form yang belum diisi (need 0 dan
     * daftar kosong) agar tidak terkesan sudah READY sebelum FC menentukan apa pun.
     *
     * @return array{0: string, 1: string}
     */
    private static function readinessBadge(Get $get): array
    {
        $need   = (int) $get('container_need');
        $listed = self::listedContainerCount($get);

        if ($need === 0 && $listed === 0) {
            return ['Belum Ditentukan', 'gray'];
        }

        return $listed >= $need
            ? ['READY', 'success']
            : ['Kurang Container', 'danger'];
    }

    private static function listedContainerCount(Get $get): int
    {
        return collect($get('container_numbers') ?? [])
            ->map(fn ($row) => strtoupper(trim((string) (is_array($row) ? ($row['number'] ?? '') : $row))))
            ->filter()
            ->unique()
            ->count();
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
