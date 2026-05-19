<?php

namespace App\Filament\Resources;

use App\Enums\VesselCheckLogStatus;
use App\Filament\Resources\VesselCheckResource\Pages;
use App\Models\VesselCheck;
use App\Models\VesselCheckCase;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;

class VesselCheckResource extends Resource
{
    protected static ?string $model = VesselCheck::class;

    protected static ?string $navigationLabel = 'Kesiapan Kapal';
    protected static ?string $pluralLabel     = 'Kesiapan Kapal';
    protected static ?string $modelLabel      = 'Kesiapan Kapal';
    protected static ?string $navigationGroup = 'Operasional';
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?int $navigationSort = 3;

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('check_date', 'asc')
            ->defaultGroup('shippingSchedule.voyage.voyage_no')

            ->groups([
                Tables\Grouping\Group::make('shippingSchedule.voyage.voyage_no')
                    ->label('Voyage')
                    ->collapsible(),
            ])

            ->columns([
                TextColumn::make('day_code')
                    ->label('H')
                    ->alignCenter()
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'D-1' => 'danger',
                        'D-2' => 'warning',
                        default => 'gray',
                    }),

                TextColumn::make('status_etd')
                    ->label('Status ETD')
                    ->badge()
                    ->getStateUsing(
                        fn($record) =>
                        static::etdIsOnSchedule($record)
                            ? 'Aman – Sesuai Jadwal'
                            : 'ETD Berubah'
                    )
                    ->color(
                        fn($record) =>
                        static::etdIsOnSchedule($record)
                            ? 'success'
                            : 'warning'
                    ),

                TextColumn::make('hasil_pemeriksaan')
                    ->label('Hasil Pemeriksaan')
                    ->badge()
                    ->getStateUsing(
                        fn($record) =>
                        static::hasOpenCase($record)
                            ? 'Perlu Tindak Lanjut'
                            : (static::etdIsOnSchedule($record)
                                ? 'Aman'
                                : 'Perlu Perhatian'
                            )
                    )
                    ->color(
                        fn($record) =>
                        static::hasOpenCase($record)
                            ? 'danger'
                            : (static::etdIsOnSchedule($record)
                                ? 'success'
                                : 'warning'
                            )
                    ),

                TextColumn::make('tindak_lanjut')
                    ->label('Tindak Lanjut')
                    ->badge()
                    ->getStateUsing(
                        fn($record) =>
                        static::etdIsOnSchedule($record)
                            ? '—'
                            : (
                                static::hasOpenCase($record)
                                    ? 'Sedang Ditangani'
                                    : 'Perlu Dibuat'
                            )
                    )
                    ->color(
                        fn($record) =>
                        static::etdIsOnSchedule($record)
                            ? 'gray'
                            : (
                                static::hasOpenCase($record)
                                    ? 'info'
                                    : 'danger'
                            )
                    ),
            ])

            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Detail'),

                Tables\Actions\Action::make('tindak_lanjut')
                    ->label(
                        fn($record) =>
                        static::hasOpenCase($record)
                            ? 'Lihat Tindak Lanjut'
                            : 'Buat Tindak Lanjut'
                    )
                    ->icon(
                        fn($record) =>
                        static::hasOpenCase($record)
                            ? 'heroicon-o-eye'
                            : 'heroicon-o-exclamation-triangle'
                    )
                    ->color(
                        fn($record) =>
                        static::hasOpenCase($record)
                            ? 'info'
                            : 'danger'
                    )
                    ->visible(
                        fn($record) =>
                        ! static::etdIsOnSchedule($record)
                    )
                    ->action(function ($record) {
                        $existingCase = $record->shippingSchedule?->vesselCheckCase;

                        if ($existingCase) {
                            return redirect()->route(
                                'filament.admin.resources.vessel-check-cases.view',
                                $existingCase
                            );
                        }

                        $status = $record->status;

                        if (! $status instanceof VesselCheckLogStatus) {
                            $status = VesselCheckLogStatus::tryFrom((string) $status)
                                ?? VesselCheckLogStatus::ON_SCHEDULE;
                        }

                        if ($status !== VesselCheckLogStatus::POTENTIAL_DELAY) {
                            \Filament\Notifications\Notification::make()
                                ->title('Tidak dapat membuat tindak lanjut')
                                ->body('Tindak lanjut hanya dapat dibuat dari status Potential Delay.')
                                ->warning()
                                ->send();
                            return;
                        }

                        VesselCheckCase::create([
                            'shipping_schedule_id' => $record->shipping_schedule_id,
                            'voyage_id'            => $record->voyage_id,
                            'case_status'          => \App\Enums\VesselCheckStatus::ETD_DELAY,
                            'delay_flag'           => true,
                            'opened_at'            => now(),
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->title('Tindak lanjut berhasil dibuat')
                            ->success()
                            ->send();
                    }),
            ])

            ->emptyStateHeading('Belum ada data pemeriksaan')
            ->emptyStateDescription('Pemeriksaan kesiapan kapal otomatis dibuat pada H-3, H-2, dan H-1 sebelum keberangkatan.');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVesselChecks::route('/'),
            'view'  => Pages\ViewVesselCheck::route('/{record}'),
        ];
    }

    // ── Operational safety helpers ───────────────────────────────────────

    public static function etdIsOnSchedule(VesselCheck $record): bool
    {
        if (! $record->etd_plan || ! $record->etd_current) {
            return true;
        }

        return $record->etd_plan->equalTo($record->etd_current);
    }

    public static function hasOpenCase(VesselCheck $record): bool
    {
        return (bool) $record->shippingSchedule?->vesselCheckCase;
    }
}
