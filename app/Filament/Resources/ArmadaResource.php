<?php

namespace App\Filament\Resources;

use App\Enums\ArmadaStatus;
use App\Enums\ArmadaType;
use App\Filament\Resources\ArmadaResource\Pages;
use App\Filament\Resources\ArmadaResource\RelationManagers\AssignmentsRelationManager;
use App\Models\Armada;
use Filament\Forms;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\View as ComponentsView;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Components\TextInput\Mask;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\HtmlString;

class ArmadaResource extends Resource
{
    protected static ?string $model = Armada::class;

    protected static ?string $navigationGroup = 'Manajemen Armada';
    protected static ?string $navigationLabel = 'Armada';
    protected static ?string $modelLabel = 'Armada';
    protected static ?string $pluralModelLabel = 'Armada';
    protected static ?string $navigationIcon = 'heroicon-m-truck';
    protected static ?int    $navigationSort  = 10;

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function form(Forms\Form $form): Forms\Form
    {
        $composePlate = function (Get $get): string {
            $pfx = strtoupper(trim((string) $get('plate_prefix')));
            $num = preg_replace('/\D+/', '', (string) $get('plate_number_digits'));
            $suf = strtoupper(trim((string) $get('plate_suffix')));

            $parts = array_filter([$pfx, $num, $suf], fn($value) => $value !== '' && $value !== null);
            return implode('', $parts);
        };

        $parseFullPlate = static function (string $raw): ?array {
            $raw = strtoupper(trim($raw));
            if (preg_match('/^([A-Z]{1,3})\s*[- ]?\s*(\d{1,4})\s*[- ]?\s*([A-Z]{0,3})$/', $raw, $m)) {
                return [$m[1] ?? '', $m[2] ?? '', $m[3] ?? ''];
            }
            if (preg_match('/^([A-Z]{1,3})\s*[- ]?\s*(\d{1,4})$/', $raw, $m)) {
                return [$m[1] ?? '', $m[2] ?? '', ''];
            }
            return null;
        };

        return $form->schema([
            Select::make('type')->label('Tipe')
                ->label('Tipe')
                ->options(collect(ArmadaType::cases())->mapWithKeys(fn($c) => [$c->value => $c->label()]))
                ->required()
                ->live()
                ->afterStateUpdated(function (Set $set, Get $get, $state) {
                    $prefix = Armada::resolvePrefixFromTypeValue((string) $state);
                    $set('code', Armada::previewNextCode($prefix, pad: 3));
                }),
            TextInput::make('code')
                ->label('Kode')
                ->disabled()
                ->dehydrated()
                ->helperText('Diisi otomatis berdasarkan Tipe'),
            Fieldset::make('No. Polisi')
                ->schema([
                    TextInput::make('plate_prefix')
                        ->label('Kode')
                        ->placeholder('B / AB')
                        ->maxLength(3)
                        ->rules(['required', 'regex:/^[A-Za-z]{1,3}$/'])
                        ->helperText('1–3 huruf. Contoh: B, AB')
                        ->reactive()
                        ->afterStateUpdated(function (Set $set, Get $get, $state) use ($composePlate, $parseFullPlate) {
                            $raw = (string) $state;

                            if ($parts = $parseFullPlate($raw)) {
                                [$pfx, $num, $suf] = $parts;
                                $set('plate_prefix', $pfx);
                                $set('plate_number_digits', $num);
                                $set('plate_suffix', $suf);
                            } else {
                                $set('plate_prefix', strtoupper(preg_replace('/[^A-Za-z]/', '', $raw)));
                            }

                            $set('plate_number', $composePlate($get));
                        })
                        ->required(),


                    TextInput::make('plate_number_digits')
                        ->label('Nomor')
                        ->placeholder('1234')
                        ->minLength(1)
                        ->maxLength(4)
                        ->extraAttributes([
                            'inputmode'    => 'numeric',
                            'pattern'      => '\d*',
                            'autocomplete' => 'off',
                            'oninput'      => "this.value=this.value.replace(/\\D+/g,'').slice(0,4)",
                        ])
                        ->rules(['required', 'regex:/^\d{1,4}$/'])
                        ->reactive()
                        ->afterStateUpdated(function (Set $set, Get $get, $state) use ($composePlate, $parseFullPlate) {
                            $raw = (string) $state;

                            if ($parts = $parseFullPlate($raw)) {
                                [$pfx, $num, $suf] = $parts;
                                $set('plate_prefix', $pfx);
                                $set('plate_number_digits', $num);
                                $set('plate_suffix', $suf);
                            } else {
                                $clean = preg_replace('/\D+/', '', $raw);
                                $set('plate_number_digits', $clean);
                            }

                            $set('plate_number', $composePlate($get));
                        }),


                    TextInput::make('plate_suffix')
                        ->label('Seri')
                        ->placeholder('XYZ')
                        ->maxLength(3)
                        ->rules(['nullable', 'regex:/^[A-Za-z]{0,3}$/'])
                        ->helperText('Opsional, 1–3 huruf')
                        ->reactive()
                        ->afterStateUpdated(function (Set $set, Get $get, $state) use ($composePlate, $parseFullPlate) {
                            $raw = (string) $state;

                            if ($parts = $parseFullPlate($raw)) {
                                [$pfx, $num, $suf] = $parts;
                                $set('plate_prefix', $pfx);
                                $set('plate_number_digits', $num);
                                $set('plate_suffix', $suf);
                            } else {
                                $set('plate_suffix', strtoupper(preg_replace('/[^A-Za-z]/', '', $raw)));
                            }

                            $set('plate_number', $composePlate($get));
                        }),
                ])
                ->columns(3),

            Placeholder::make('plate_preview')
                ->label('Pratinjau No. Polisi')
                ->content(function (Get $get) use ($composePlate) {
                    $plate = $composePlate($get);
                    if (! $plate) {
                        return '—';
                    }

                    $safe   = e($plate);
                    $pretty = str_replace(' ', '&nbsp;&nbsp;', $safe);

                    return new HtmlString(
                        '<div class="inline-flex items-center gap-2 rounded-lg bg-gray-50 px-3 py-2">
                <span class="font-mono tracking-wider text-base">' . $pretty . '</span>
            </div>'
                    );
                })
                ->inlineLabel()
                ->columnSpanFull(),


            TextInput::make('plate_number')
                ->hidden()
                ->dehydrated()
                ->dehydrateStateUsing(fn(Get $get) => $composePlate($get))
                ->afterStateHydrated(function (Set $set, $state) {
                    $plate = strtoupper(trim((string) $state));
                    if ($plate === '') return;

                    if (preg_match('/^([A-Z]{1,3})\s*(\d{1,4})(?:\s*([A-Z]{1,3}))?$/', $plate, $m)) {
                        $set('plate_prefix',        $m[1] ?? null);
                        $set('plate_number_digits', $m[2] ?? null);
                        $set('plate_suffix',        $m[3] ?? null);
                    } else {
                        $set('plate_prefix', null);
                        $set('plate_number_digits', null);
                        $set('plate_suffix', null);
                    }
                }),

            TextInput::make('capacity')->numeric()->label('Kapasitas'),

            ComponentsView::make('components.form-armada-status-badge')
                ->label('Status')
                ->viewData(
                    fn(Get $get, ?Armada $record) => [
                        'label' => $record?->status?->label() ?? ArmadaStatus::Available->label(),
                        'color' => $record?->status?->color() ?? 'success',
                    ]
                )
                ->columnSpanFull(),

            Select::make('branch_id')->relationship('branch', 'name')->label('Cabang')->required(),
            Select::make('depot_id')->relationship('depot', 'name')->label('Depot'),
            Textarea::make('notes')->label('Catatan')->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table->columns([
            TextColumn::make('code')
                ->badge()
                ->label('Kode')
                ->searchable(),
            TextColumn::make('type')
                ->label('Tipe')->badge()
                ->state(fn($record) => is_object($record->type) && method_exists($record->type, 'label') ? $record->type->label() : (string) $record->type),
            TextColumn::make('plate_number')
                ->label('No. Polisi')
                ->searchable(),
            TextColumn::make('capacity')
                ->label('Kapasitas')
                ->numeric(),
            TextColumn::make('status')->label('Status')->badge()
                ->color(fn($record) => is_object($record->status) && method_exists($record->status, 'color') ? $record->status->color() : 'gray')
                ->state(fn($record) => is_object($record->status) && method_exists($record->status, 'label') ? $record->status->label() : (string) $record->status),
            TextColumn::make('branch.name')
                ->label('Cabang')
                ->badge(),
            TextColumn::make('depot.name')
                ->label('Depot'),
            TextColumn::make('updated_at')
                ->since()
                ->label('Diubah'),
        ])->filters([
            Tables\Filters\SelectFilter::make('type')
                ->options(collect(ArmadaType::cases())->mapWithKeys(fn($c) => [$c->value => $c->label()])),
            Tables\Filters\SelectFilter::make('status')
                ->options(collect(ArmadaStatus::cases())->mapWithKeys(fn($c) => [$c->value => $c->label()])),
        ])->actions([
            Tables\Actions\EditAction::make()->label('Ubah'),
        ])->bulkActions([
            Tables\Actions\DeleteBulkAction::make()->label('Hapus'),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListArmadas::route('/'),
            'create' => Pages\CreateArmada::route('/create'),
            'edit'   => Pages\EditArmada::route('/{record}/edit'),
        ];
    }
}
