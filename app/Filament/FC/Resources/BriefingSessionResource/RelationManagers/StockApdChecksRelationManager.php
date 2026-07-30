<?php

namespace App\Filament\FC\Resources\BriefingSessionResource\RelationManagers;

use App\Models\StockApdCheck;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;

class StockApdChecksRelationManager extends RelationManager
{
    protected static string $relationship = 'stockApdChecks';

    protected static ?string $title = 'Stok APD';

    protected static ?string $recordTitleAttribute = 'ppe_type';

    // ─── PPE type lookup ────────────────────────────────────────────────────

    private const PPE_LABELS = [
        'helm'          => 'Helm Safety',
        'rompi'         => 'Rompi',
        'sepatu'        => 'Sepatu Safety',
        'sarung_tangan' => 'Sarung Tangan',
    ];

    // ─── Form ───────────────────────────────────────────────────────────────

    public function form(Form $form): Form
    {
        return $form->schema([

            // Create mode — jenis APD masih harus dipilih.
            Select::make('ppe_type')
                ->label('Jenis APD')
                ->options(function () {
                    // Kecualikan jenis yang sudah ada agar tidak menabrak UNIQUE.
                    $session  = $this->getOwnerRecord();
                    $existing = StockApdCheck::where('session_id', $session->id)
                        ->pluck('ppe_type')
                        ->toArray();

                    return collect(self::PPE_LABELS)
                        ->reject(fn ($value, $key) => in_array($key, $existing, true))
                        ->toArray();
                })
                ->required()
                ->native(false)
                ->visible(fn (?StockApdCheck $record) => $record === null),

            // Edit mode — jenis APD tidak pernah dapat diubah, jadi tampil sebagai
            // informasi saja (bukan Select yang ter-disable).
            Placeholder::make('ppe_type_display')
                ->label('Jenis APD')
                ->content(fn (?StockApdCheck $record): string => self::ppeLabel($record?->ppe_type))
                ->visible(fn (?StockApdCheck $record) => $record !== null),

            Placeholder::make('required_display')
                ->label('Kebutuhan')
                ->content(fn (?StockApdCheck $record): string => $this->requiredQuantity($record) . ' unit'),

            TextInput::make('stock_available')
                ->label('Stok Hari Ini')
                ->helperText('Jumlah APD yang benar-benar tersedia hari ini.')
                ->numeric()
                ->minValue(0)
                ->placeholder('—')
                ->live(onBlur: true),

            Placeholder::make('gap_display')
                ->label('Gap')
                ->content(function (Get $get, ?StockApdCheck $record): string {
                    $stock = $get('stock_available');

                    if ($stock === null || $stock === '') {
                        return '—';
                    }

                    $gap = (int) $stock - $this->requiredQuantity($record);

                    return $gap >= 0 ? "+{$gap}" : (string) $gap;
                }),

            Placeholder::make('status_display')
                ->label('Status')
                ->content(function (Get $get, ?StockApdCheck $record): HtmlString {
                    [$label, $color] = $this->statusBadge($get, $record);

                    return new HtmlString(Blade::render(
                        '<x-filament::badge :color="$color" size="lg">{{ $label }}</x-filament::badge>',
                        ['color' => $color, 'label' => $label],
                    ));
                }),

            Textarea::make('remark')
                ->label('Catatan')
                ->rows(2)
                ->maxLength(500)
                ->columnSpanFull(),

        ])->columns(2);
    }

    // ─── Form helpers (presentation only) ───────────────────────────────────

    private static function ppeLabel(?string $type): string
    {
        return self::PPE_LABELS[strtolower((string) $type)] ?? (string) $type ?: '—';
    }

    /**
     * Kebutuhan APD. Pada record existing memakai nilai tersimpan; saat membuat
     * baru, mengikuti target SOP sesi (sama seperti yang diisi CreateAction).
     */
    private function requiredQuantity(?StockApdCheck $record): int
    {
        return (int) ($record?->required_quantity
            ?? $this->getOwnerRecord()->summary_headcount
            ?? 0);
    }

    /**
     * Label + warna badge Status.
     *
     * Memakai StockApdCheck::getComputedStatusAttribute() apa adanya — rule tidak
     * diduplikasi maupun diubah, hanya dievaluasi pada nilai yang sedang diisi
     * di form agar preview bersifat live.
     *
     * @return array{0: string, 1: string}
     */
    private function statusBadge(Get $get, ?StockApdCheck $record): array
    {
        $stock = $get('stock_available');

        $probe = new StockApdCheck([
            'stock_available'   => ($stock === null || $stock === '') ? null : (int) $stock,
            'required_quantity' => $this->requiredQuantity($record),
        ]);

        return match ($probe->computed_status) {
            'cukup'  => ['Cukup', 'success'],
            'kurang' => ['Kurang', 'danger'],
            default  => ['Belum Diisi', 'warning'],
        };
    }

    // ─── Table ──────────────────────────────────────────────────────────────

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('ppe_type')
            ->defaultSort('ppe_type')
            ->columns([

                TextColumn::make('ppe_type')
                    ->label('Jenis APD')
                    ->formatStateUsing(
                        fn ($state) => self::PPE_LABELS[strtolower((string) $state)] ?? (string) $state
                    )
                    ->weight('bold')
                    ->icon('heroicon-o-shield-check'),

                TextColumn::make('stock_available')
                    ->label('Stok')
                    ->placeholder('—')
                    ->alignCenter()
                    ->suffix(fn ($state) => $state !== null ? ' unit' : ''),

                TextColumn::make('required_quantity')
                    ->label('Kebutuhan')
                    ->placeholder('—')
                    ->alignCenter()
                    ->suffix(fn ($state) => $state !== null ? ' unit' : ''),

                TextColumn::make('gap')
                    ->label('Gap')
                    ->formatStateUsing(fn ($state) => match (true) {
                        $state === null => '—',
                        $state > 0      => "+{$state}",
                        default         => (string) $state,
                    })
                    ->color(fn ($record) => match (true) {
                        $record->gap === null => 'gray',
                        $record->gap > 0      => 'success',
                        $record->gap === 0    => 'gray',
                        default               => 'danger',
                    })
                    ->weight(fn ($record) => $record->gap !== null && $record->gap < 0 ? 'bold' : null)
                    ->alignCenter(),

                TextColumn::make('computed_status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'cukup'       => 'Cukup',
                        'kurang'      => 'Kurang',
                        'belum_diisi' => 'Belum Diisi',
                        default       => (string) $state,
                    })
                    ->color(fn ($state) => match ($state) {
                        'cukup'  => 'success',
                        'kurang' => 'danger',
                        default  => 'gray',
                    }),

                TextColumn::make('remark')
                    ->label('Catatan')
                    ->limit(30)
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

            ])
            ->headerActions([

                Tables\Actions\CreateAction::make()
                    ->label('Tambah APD')
                    ->modalHeading('Pemeriksaan Stok APD')
                    ->modalDescription('Tambahkan jenis APD yang diperiksa hari ini.')
                    ->modalSubmitActionLabel('Simpan Pemeriksaan')
                    // Hide when session is terminal or all 4 types are already covered.
                    ->visible(fn () => ! $this->getOwnerRecord()->isTerminal()
                        && StockApdCheck::where(
                            'session_id', $this->getOwnerRecord()->id
                        )->count() < count(self::PPE_LABELS))
                    ->mutateFormDataUsing(function (array $data) {
                        $session = $this->getOwnerRecord();
                        $data['session_id']        = $session->id;
                        // Auto-fill required_quantity from session headcount.
                        $data['required_quantity'] = (int) ($session->summary_headcount ?? 0);

                        return $data;
                    }),

                Tables\Actions\Action::make('generateAll')
                    ->label('Generate Semua APD')
                    ->icon('heroicon-o-sparkles')
                    ->color('info')
                    ->visible(fn () => ! $this->getOwnerRecord()->isTerminal())
                    ->requiresConfirmation()
                    ->modalHeading('Generate Stok APD')
                    ->modalDescription(
                        'Buat otomatis 4 item APD (Helm, Rompi, Sepatu, Sarung Tangan) untuk sesi ini. '
                        .'Item yang sudah ada tidak akan ditimpa.'
                    )
                    ->action(function () {
                        $session   = $this->getOwnerRecord();
                        $headcount = (int) ($session->summary_headcount ?? 0);
                        $created   = 0;

                        foreach (array_keys(self::PPE_LABELS) as $type) {
                            $item = StockApdCheck::firstOrCreate(
                                ['session_id' => $session->id, 'ppe_type' => $type],
                                [
                                    'required_quantity' => $headcount,
                                    'stock_available'   => null,
                                    'remark'            => null,
                                ]
                            );

                            if ($item->wasRecentlyCreated) {
                                $created++;
                            }
                        }

                        $msg = $created > 0
                            ? "{$created} item APD berhasil digenerate."
                            : 'Semua item APD sudah tersedia — tidak ada yang ditambahkan.';

                        Notification::make()
                            ->title($msg)
                            ->success()
                            ->send();
                    }),

            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Periksa')
                    ->icon('heroicon-m-clipboard-document-check')
                    ->modalHeading('Pemeriksaan Stok APD')
                    ->modalDescription(fn (StockApdCheck $record): string => self::ppeLabel($record->ppe_type))
                    ->modalSubmitActionLabel('Simpan Pemeriksaan')
                    ->visible(fn () => ! $this->getOwnerRecord()->isTerminal()),
                Tables\Actions\DeleteAction::make()
                    ->label('Hapus')
                    ->visible(fn () => ! $this->getOwnerRecord()->isTerminal()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Hapus Terpilih')
                        ->visible(fn () => ! $this->getOwnerRecord()->isTerminal()),
                ]),
            ]);
    }
}
