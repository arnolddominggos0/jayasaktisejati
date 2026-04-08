<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VesselCheckResource\Pages;
use App\Models\VesselCheck;
use App\Services\VesselCheckService;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;

class VesselCheckResource extends Resource
{
    protected static ?string $model = VesselCheck::class;

    protected static ?string $navigationLabel = 'Pemeriksaan Jadwal Kapal';
    protected static ?string $pluralLabel     = 'Pemeriksaan Jadwal Kapal';
    protected static ?string $modelLabel      = 'Pemeriksaan Jadwal Kapal';
    protected static ?string $navigationGroup = 'Monitoring Kapal TAM';
    protected static ?string $navigationIcon  = 'heroicon-o-clipboard-document-check';
    protected static ?int $navigationSort = 2;

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
                    ->getStateUsing(
                        fn($record) =>
                        $record->shippingSchedule->vesselCheckCase
                            ? 'Perlu Tindak Lanjut'
                            : (
                                $record->etd_plan->equalTo($record->etd_current)
                                ? 'Aman'
                                : 'Perlu Perhatian'
                            )
                    )
                    ->color(
                        fn($record) =>
                        $record->shippingSchedule->vesselCheckCase
                            ? 'danger'
                            : (
                                $record->etd_plan->equalTo($record->etd_current)
                                ? 'success'
                                : 'warning'
                            )
                    ),

                TextColumn::make('tindak_lanjut')
                    ->label('Tindak Lanjut')
                    ->badge()
                    ->getStateUsing(
                        fn($record) =>
                        $record->etd_plan->equalTo($record->etd_current)
                            ? '—'
                            : (
                                $record->shippingSchedule->vesselCheckCase
                                ? 'Sedang Ditangani'
                                : 'Perlu Dibuat'
                            )
                    )
                    ->color(
                        fn($record) =>
                        $record->etd_plan->equalTo($record->etd_current)
                            ? 'gray'
                            : (
                                $record->shippingSchedule->vesselCheckCase
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
                    )
                    ->action(function ($record) {
                        if ($record->shippingSchedule->vesselCheckCase) {
                            return redirect()->route(
                                'filament.admin.resources.vessel-check-cases.view',
                                $record->shippingSchedule->vesselCheckCase
                            );
                        }

                        app(VesselCheckService::class)
                            ->openIssueFromCheck($record->id);
                    }),
            ])

            ->emptyStateHeading('Belum ada data pemeriksaan')
            ->emptyStateDescription('Pemeriksaan jadwal kapal otomatis dibuat pada H-3, H-2, dan H-1 sebelum keberangkatan.');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVesselChecks::route('/'),
            'view'  => Pages\ViewVesselCheck::route('/{record}'),
        ];
    }
}
