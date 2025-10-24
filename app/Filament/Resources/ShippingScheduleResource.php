<?php

namespace App\Filament\Resources;

use App\Enums\ScheduleState;
use App\Filament\Resources\ShippingScheduleResource\Pages;
use App\Filament\Resources\ShippingScheduleResource\RelationManagers\ItemsRelationManager;
use App\Models\ShippingSchedule;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Illuminate\Support\Carbon;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use App\Actions\Schedule\SyncVoyagesToSchedule;

class ShippingScheduleResource extends Resource
{
    protected static ?string $model = ShippingSchedule::class;
    protected static ?string $navigationGroup = 'Pelayaran & Kapal';
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationLabel = 'Jadwal Kapal (TAM)';
    protected static ?string $pluralLabel = 'Jadwal Kapal (TAM)';
    protected static ?string $slug = 'shipping-schedules';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Informasi Jadwal')
                ->schema([
                    TextInput::make('code')->label('Kode Jadwal')->maxLength(50)->unique(ignoreRecord: true),
                    Select::make('state')->label('Status')->options(ScheduleState::options())->disabled(),
                    DatePicker::make('period_month')->label('Periode Bulan')->displayFormat('F Y')->native(false)->default(now()->startOfMonth()),
                    TextInput::make('vessel_name')->label('Nama Kapal')->maxLength(120)->nullable(),
                    TextInput::make('voyage_no')->label('Voyage No')->maxLength(50)->nullable(),
                    DateTimePicker::make('etd')->label('ETD')->nullable(),
                    DateTimePicker::make('eta')->label('ETA')->nullable(),
                    TextInput::make('cargo_plan_total')->label('Cargo Plan')->numeric()->minValue(0)->nullable(),
                ])->columns(2),

            Section::make('Finalisasi TAM')
                ->schema([
                    TextInput::make('approved_by_name')->label('Disetujui oleh (TAM)')->disabled(),
                    Textarea::make('final_note')->label('Catatan Final (TAM)')->disabled()->rows(3),
                    TextInput::make('final_source')->label('Sumber Final')->disabled(),
                    DateTimePicker::make('approved_at')->label('Tanggal Disetujui')->disabled(),
                    TextInput::make('final_attachment_path')->label('Lampiran')->disabled(),
                    TextInput::make('final_email_from')->label('Email From')->disabled(),
                    TextInput::make('final_email_subject')->label('Subject Email')->disabled(),
                    DateTimePicker::make('final_email_received_at')->label('Diterima Pada')->disabled(),
                ])->columns(2),
        ])->columns(1);
    }

    public static function table(\Filament\Tables\Table $table): \Filament\Tables\Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('code')->label('Kode')->searchable()->sortable(),
                \Filament\Tables\Columns\TextColumn::make('state')->label('Status')->state(fn($record) => $record->state instanceof ScheduleState ? $record->state->value : $record->state)->badge()->colors(['warning' => 'draft', 'success' => 'final'])->formatStateUsing(fn(string $state) => ScheduleState::options()[$state] ?? ucfirst($state)),
                \Filament\Tables\Columns\TextColumn::make('period_month')->label('Periode')->formatStateUsing(fn($state) => $state ? Carbon::parse($state)->translatedFormat('F Y') : '-')->toggleable(),
                \Filament\Tables\Columns\TextColumn::make('vessel_name')->label('Kapal')->searchable(),
                \Filament\Tables\Columns\TextColumn::make('voyage_no')->label('Voyage')->toggleable(),
                \Filament\Tables\Columns\TextColumn::make('etd')->label('ETD')->dateTime('d M Y H:i'),
                \Filament\Tables\Columns\TextColumn::make('eta')->label('ETA')->dateTime('d M Y H:i'),
                \Filament\Tables\Columns\TextColumn::make('revision_count')->label('Rev')->sortable()->alignCenter(),
            ])
            ->actions([
                Action::make('final_from_email')
                    ->label('Final dari Email TAM')
                    ->icon('heroicon-o-check-circle')
                    ->visible(fn(ShippingSchedule $record) => $record->canFinalizeFromEmail())
                    ->form([
                        Forms\Components\TextInput::make('approved_by_name')->label('Disetujui oleh (TAM)')->required(),
                        DatePicker::make('period_month')->label('Periode Bulan')->displayFormat('F Y')->native(false)->default(now()->startOfMonth()),
                        Forms\Components\TextInput::make('email_from')->label('Email From'),
                        Forms\Components\TextInput::make('email_subject')->label('Subject Email'),
                        Forms\Components\DateTimePicker::make('email_received_at')->label('Diterima Pada')->default(now()),
                        Forms\Components\Textarea::make('final_note')->label('Catatan Final')->rows(4),
                        FileUpload::make('final_attachment')->label('Lampiran Email (PDF/Excel/IMG)')->directory('schedules/' . date('Y/m'))->disk('public')->visibility('public')->preserveFilenames()->acceptedFileTypes(['application/pdf', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'image/*'])->maxSize(20 * 1024),
                        Forms\Components\Textarea::make('paste_table')->label('Tempel Tabel Final')->rows(12),
                    ])
                    ->action(function (array $data, ShippingSchedule $record) {
                        $path = null;
                        if (!empty($data['final_attachment'])) {
                            $path = is_string($data['final_attachment']) ? $data['final_attachment'] : (is_array($data['final_attachment']) ? ($data['final_attachment'][0] ?? null) : null);
                        }

                        $record->finalizeFromEmail([
                            'approved_by_name'      => $data['approved_by_name'] ?? null,
                            'final_note'            => $data['final_note'] ?? null,
                            'final_attachment_path' => $path,
                            'approved_at'           => now(),
                        ]);

                        if (!empty($data['period_month'])) {
                            $record->period_month = Carbon::parse($data['period_month'])->startOfMonth();
                        } elseif (!$record->period_month) {
                            $base = $record->etd ?: $record->approved_at ?: now();
                            $record->period_month = Carbon::parse($base)->startOfMonth();
                        }

                        $record->setEmailFinalMeta([
                            'message_id' => null,
                            'from'       => $data['email_from'] ?? null,
                            'subject'    => $data['email_subject'] ?? null,
                            'received_at' => $data['email_received_at'] ?? now(),
                        ]);
                        $record->save();

                        if (!empty($data['paste_table'])) {
                            $rows = \App\Support\TamFinalTableParser::parse($data['paste_table']);
                            $record->items()->delete();
                            foreach ($rows as $r) {
                                $record->items()->create([
                                    'etd'             => $r['etd'],
                                    'eta'             => $r['eta'],
                                    'cargo_plan'      => $r['cargo_plan'],
                                    'vessel_name'     => $r['vessel_name'],
                                    'vessel_capacity' => $r['vessel_capacity'],
                                    'voyage_no'       => $r['voyage_no'],
                                    'jss'             => $r['jss'],
                                    'lts'             => $r['lts'],
                                    'dwelling'        => $r['dwelling'],
                                ]);
                            }
                            if (!empty($rows)) {
                                $first = $rows[0];
                                $record->etd         = $record->etd         ?: ($first['etd'] ?? null);
                                $record->eta         = $record->eta         ?: ($first['eta'] ?? null);
                                $record->vessel_name = $record->vessel_name ?: ($first['vessel_name'] ?? null);
                                $record->voyage_no   = $record->voyage_no   ?: ($first['voyage_no'] ?? null);
                                $record->cargo_plan_total = $record->items()->sum('cargo_plan');
                                $record->save();
                            }
                        }

                        SyncVoyagesToSchedule::run($record);

                        Notification::make()->title('Final diterapkan dan disinkronkan ke Data Pelayaran')->success()->send();
                    }),
            ])
            ->defaultSort('etd', 'asc');
    }

    public static function getRelations(): array
    {
        return [
            ItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListShippingSchedules::route('/'),
            'create' => Pages\CreateShippingSchedule::route('/create'),
            'edit'   => Pages\EditShippingSchedule::route('/{record}/edit'),
        ];
    }
}
