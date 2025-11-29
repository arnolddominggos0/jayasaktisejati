<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TamMonthlyScheduleResource\Pages;
use App\Models\TamMonthlySchedule;
use App\Models\ShippingSchedule;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;

class TamMonthlyScheduleResource extends Resource
{
    protected static ?string $model = TamMonthlySchedule::class;

    protected static ?string $navigationGroup = 'TAM';
    protected static ?string $navigationLabel = 'Paket Bulanan TAM';
    protected static ?string $navigationIcon = 'heroicon-o-archive-box';
    protected static ?int $navigationSort = 20;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\DatePicker::make('period_month')
                ->label('Periode Bulan')
                ->required()
                ->native(false)
                ->displayFormat('F Y')
                ->closeOnDateSelection(),
            Forms\Components\TextInput::make('version')
                ->default('v1.0')
                ->label('Versi'),
            Forms\Components\Textarea::make('draft_message')
                ->label('Pesan Draft WA')
                ->rows(8),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('period_month')
                    ->label('Periode')
                    ->date('F Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state) => match ($state) {
                        'draft' => 'warning',
                        'sent' => 'info',
                        'feedback' => 'purple',
                        'final' => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('total_plan')
                    ->label('Total Plan')
                    ->numeric(),
                Tables\Columns\TextColumn::make('draft_path')
                    ->label('Draft')
                    ->formatStateUsing(fn($state) => $state ? 'Unduh' : '-')
                    ->url(fn($record) => $record->draft_path ? asset('storage/' . $record->draft_path) : null, true),
                Tables\Columns\TextColumn::make('final_path')
                    ->label('Final')
                    ->formatStateUsing(fn($state) => $state ? 'Unduh' : '-')
                    ->url(fn($record) => $record->final_path ? asset('storage/' . $record->final_path) : null, true),
                Tables\Columns\TextColumn::make('generated_by_name')
                    ->label('Dibuat oleh'),
                Tables\Columns\TextColumn::make('generated_at')
                    ->label('Dibuat')
                    ->dateTime(),
                Tables\Columns\TextColumn::make('finalized_at')
                    ->label('Final')
                    ->dateTime(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options([
                    'draft' => 'Draft',
                    'sent' => 'Sent',
                    'feedback' => 'Feedback',
                    'final' => 'Final',
                ]),
            ])
            ->actions([
                Tables\Actions\Action::make('generateDraft')
                    ->label('Generate Draft')
                    ->icon('heroicon-o-document-plus')
                    ->requiresConfirmation()
                    ->action(function (TamMonthlySchedule $record) {
                        $period = $record->period_month instanceof Carbon
                            ? $record->period_month->copy()->startOfMonth()
                            : Carbon::parse($record->period_month)->startOfMonth();

                        $year = (int) $period->year;
                        $month = (int) $period->month;

                        $schedules = ShippingSchedule::query()
                            ->with(['vessel', 'pod'])
                            ->whereYear('etd', $year)
                            ->whereMonth('etd', $month)
                            ->where('state', 'final')
                            ->whereHas('pod', function ($q) {
                                $q->where('code', 'MND');
                            })
                            ->orderBy('etd')
                            ->get();

                        $totalPlan = (int) $schedules->sum('cargo_plan');

                        $lines = [];
                        $lines[] = 'selamat pagi pak';
                        $lines[] = 'terlampir jadwal kapal TAM bulan ' . $period->translatedFormat('F Y');
                        $lines[] = '';

                        foreach ($schedules as $schedule) {
                            $vessel = $schedule->vessel->name ?? $schedule->vessel_name ?? '-';
                            $voyage = $schedule->voyage_no ?: '-';
                            $etd = $schedule->etd ? $schedule->etd->format('d-m-Y') : '-';
                            $lines[] = '• ' . $vessel . ' V. ' . $voyage . ' ETD ' . $etd;
                        }

                        if ($schedules->isEmpty()) {
                            $lines[] = '(belum ada jadwal kapal final untuk periode ini)';
                        }

                        $lines[] = 'jadwal dapat berubah sewaktu-waktu';
                        $lines[] = 'terima kasih';

                        $message = implode(PHP_EOL, $lines);

                        $record->update([
                            'total_plan'        => $totalPlan,
                            'draft_message'     => $message,
                            'generated_by_name' => auth_user()->name ?? 'System',
                            'generated_at'      => now(),
                        ]);
                    }),
                Tables\Actions\Action::make('openWhatsapp')
                    ->label('Buka WA')
                    ->icon('heroicon-o-chat-bubble-left-ellipsis')
                    ->url(function (TamMonthlySchedule $record) {
                        $phone = config('services.tam.whatsapp_number', '62812xxxxxxxx');
                        $text = $record->draft_message ?: 'Draft jadwal kapal belum digenerate.';
                        return 'https://wa.me/' . $phone . '?text=' . urlencode($text);
                    })
                    ->openUrlInNewTab()
                    ->visible(fn(TamMonthlySchedule $record) => filled($record->draft_message)),
                Tables\Actions\Action::make('finalizeAll')
                    ->label('Tandai Final')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (TamMonthlySchedule $record) {
                        $record->update([
                            'status'       => 'final',
                            'finalized_at' => now(),
                        ]);
                    })
                    ->visible(fn(TamMonthlySchedule $record) => in_array($record->status, ['draft', 'feedback', 'sent'], true)),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListTamMonthlySchedules::route('/'),
            'create' => Pages\CreateTamMonthlySchedule::route('/create'),
            'edit'   => Pages\EditTamMonthlySchedule::route('/{record}/edit'),
        ];
    }
}
