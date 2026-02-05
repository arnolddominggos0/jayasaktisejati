<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VesselCheckResource\Pages;
use App\Models\VesselCheck;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;

class VesselCheckResource extends Resource
{
    protected static ?string $model = VesselCheck::class;

    protected static ?string $navigationLabel = 'Pemeriksaan Jadwal Kapal (H-3 s.d. H-1)';
    protected static ?string $navigationGroup = 'Monitoring Kapal TAM';
    protected static ?string $navigationIcon  = 'heroicon-o-calendar-days';
    protected static ?int $navigationSort = 2;

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('check_date', 'asc')

            ->groups([
                Tables\Grouping\Group::make('shippingSchedule.voyage.voyage_no')
                    ->label('Voyage')
                    ->collapsible(),
            ])

            ->columns([
                TextColumn::make('day_code')
                    ->label('H')
                    ->badge()
                    ->alignCenter()
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
                        $record->etd_plan->equalTo($record->etd_current)
                            ? 'Aman – Sesuai Jadwal'
                            : 'ETD Berubah'
                    )
                    ->color(
                        fn($record) =>
                        $record->etd_plan->equalTo($record->etd_current)
                            ? 'success'
                            : 'warning'
                    ),

                TextColumn::make('hasil_pemeriksaan')
                    ->label('Hasil Pemeriksaan')
                    ->badge()
                    ->getStateUsing(function ($record) {
                        if ($record->shippingSchedule->vesselCheckCase) {
                            return 'Perlu Tindak Lanjut';
                        }

                        if (! $record->etd_plan->equalTo($record->etd_current)) {
                            return 'Perlu Perhatian';
                        }

                        return 'Aman';
                    })
                    ->color(function ($record) {
                        if ($record->shippingSchedule->vesselCheckCase) {
                            return 'danger';
                        }

                        if (! $record->etd_plan->equalTo($record->etd_current)) {
                            return 'warning';
                        }

                        return 'success';
                    }),

                TextColumn::make('shippingSchedule.vesselCheckCase.case_status')
                    ->label('Tindak Lanjut')
                    ->badge()
                    ->formatStateUsing(fn($state) => $state?->label() ?? 'Belum Ada')
                    ->color(fn($state) => $state?->color() ?? 'gray'),
            ])

            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Detail'),

                Tables\Actions\Action::make('tindak_lanjut')
                    ->label(
                        fn($record) =>
                        $record->shippingSchedule->vesselCheckCase
                            ? 'Lihat Tindak Lanjut'
                            : 'Buat Tindak Lanjut'
                    )
                    ->icon(
                        fn($record) =>
                        $record->shippingSchedule->vesselCheckCase
                            ? 'heroicon-o-eye'
                            : 'heroicon-o-exclamation-triangle'
                    )
                    ->color(
                        fn($record) =>
                        $record->shippingSchedule->vesselCheckCase
                            ? 'info'
                            : 'danger'
                    )
                    ->visible(
                        fn($record) =>
                        ! $record->etd_plan->equalTo($record->etd_current)
                            || $record->shippingSchedule->vesselCheckCase
                    )
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        if ($record->shippingSchedule->vesselCheckCase) {
                            return redirect()->route(
                                'filament.admin.resources.vessel-check-cases.view',
                                $record->shippingSchedule->vesselCheckCase
                            );
                        }

                        app(\App\Services\VesselCheckService::class)
                            ->openIssueFromCheck($record->id);
                    }),
            ])

            ->emptyStateHeading('Belum ada data pemeriksaan')
            ->emptyStateDescription('Pemeriksaan otomatis dibuat pada H-3, H-2, dan H-1 sebelum keberangkatan.');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVesselChecks::route('/'),
            'view'  => Pages\ViewVesselCheck::route('/{record}'),
        ];
    }
}
